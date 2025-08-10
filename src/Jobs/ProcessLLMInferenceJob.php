<?php

namespace Lanos\LLMProcessor\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Lanos\LLMProcessor\Models\LLMInteraction;
use Lanos\LLMProcessor\Models\LLMProcess;
use Lanos\LLMProcessor\Services\TemplateProcessor;
use Lanos\LLMProcessor\Services\OpenRouterClient;
use Lanos\LLMProcessor\Services\AttachmentResolver;
use Lanos\LLMProcessor\Exceptions\ModelNotFoundException;
use Lanos\LLMProcessor\Exceptions\LLMProcessException;

class ProcessLLMInferenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The interaction ID.
     *
     * @var string
     */
    public string $interactionId;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout;

    /**
     * Create a new job instance.
     *
     * @param string $interactionId
     */
    public function __construct(string $interactionId)
    {
        $this->interactionId = $interactionId;
        $this->timeout = config('llm-processor.openrouter.timeout', 60);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Load LLMInteraction and update status to 'processing'
        $interaction = LLMInteraction::findOrFail($this->interactionId);
        
        $interaction->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            // Load LLMProcess
            $process = $interaction->process;
            
            if (!$process) {
                throw new LLMProcessException("Process not found for interaction {$this->interactionId}");
            }
            
            // Load model with dependencies
            $model = $process->getModelInstance($interaction->model_id);
            
            if (!$model) {
                throw new ModelNotFoundException("Model {$interaction->model_type} with ID {$interaction->model_id} not found");
            }
            
            // Flatten model data to array
            $templateProcessor = new TemplateProcessor();
            $data = $templateProcessor->flattenArray($model->toArray());
            
            // Process templates with TemplateProcessor
            $systemPrompt = $templateProcessor->process($interaction->system_prompt, $data);
            $userPrompt = $templateProcessor->process($interaction->user_prompt, $data);
            
            // Resolve attachments with AttachmentResolver
            $attachments = [];
            $attachmentResolver = new AttachmentResolver();
            
            if (!empty($process->attachments)) {
                $attachments = $attachmentResolver->resolve($process->attachments, $data);
            }
            
            // Update interaction with attachments
            $interaction->update(['attachments' => $attachments]);
            
            // Build OpenRouter request
            $openRouterClient = new OpenRouterClient();
            $messages = $openRouterClient->formatMessages($systemPrompt, $userPrompt, $attachments);
            
            // Send request via OpenRouterClient
            $response = $openRouterClient->chat($messages, [
                'model' => $process->model,
                'temperature' => $process->temperature,
                'max_tokens' => $process->max_output_tokens,
                'structured_output_schema' => $process->structured_output_schema,
                'use_web_search' => $process->use_web_search,
                'use_reasoning' => $process->use_reasoning,
            ]);
            
            // Store response in LLMInteraction
            $interaction->update([
                'response' => $response->content,
                'response_metadata' => [
                    'model' => $response->model ?? null,
                    'tokens_used' => $response->tokens_used ?? null,
                ],
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Update interaction status to 'failed'
            $interaction->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            
            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }
}