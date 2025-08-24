<?php

namespace Lanos\LLMProcessor\Services;

use Lanos\LLMProcessor\Models\LLMProcess;
use Lanos\LLMProcessor\Models\LLMInteraction;
use Lanos\LLMProcessor\Exceptions\ModelNotFoundException;
use Lanos\LLMProcessor\Exceptions\LLMProcessException;
use Lanos\LLMProcessor\Jobs\ProcessLLMInferenceJob;
use Illuminate\Database\Eloquent\Model;

class LLMInferenceService
{
    /**
     * Template processor instance.
     *
     * @var TemplateProcessor
     */
    protected TemplateProcessor $templateProcessor;

    /**
     * OpenRouter client instance.
     *
     * @var OpenRouterClient
     */
    protected OpenRouterClient $openRouterClient;

    /**
     * Attachment resolver instance.
     *
     * @var AttachmentResolver
     */
    protected AttachmentResolver $attachmentResolver;

    /**
     * Create a new LLMInferenceService instance.
     *
     * @param TemplateProcessor $templateProcessor
     * @param OpenRouterClient $openRouterClient
     * @param AttachmentResolver $attachmentResolver
     */
    public function __construct(
        TemplateProcessor $templateProcessor,
        OpenRouterClient $openRouterClient,
        AttachmentResolver $attachmentResolver
    ) {
        $this->templateProcessor = $templateProcessor;
        $this->openRouterClient = $openRouterClient;
        $this->attachmentResolver = $attachmentResolver;
    }

    /**
     * Process LLM inference.
     *
     * @param string $processId
     * @param string $modelId
     * @param array $context
     * @param bool $async
     * @return LLMInteraction
     * @throws ModelNotFoundException
     * @throws LLMProcessException
     */
    public function processInference(string $processId, string $modelId, array $context = [], bool $async = true, string | false $overrideUserPrompt = false): LLMInteraction
    {
        // Load the LLMProcess
        $process = LLMProcess::findOrFail($processId);
        
        // Validate the process
        $process->validate();
        
        // Load the model instance with dependencies
        $model = $this->loadModelWithDependencies($process, $modelId);
        
        if (!$model) {
            throw new ModelNotFoundException("Model {$process->model_class} with ID {$modelId} not found");
        }
        
        // Prepare data for template processing
        $data = $this->prepareData($process, $model);
        
        // Merge with any additional context
        $data = array_merge($data, $context);
        
        // Validate data requirements
        $validationResult = $this->validateDataRequirements($process, $data);
        
        if (!$validationResult['valid'] && $process->terminate_on_missing_data) {
            throw new LLMProcessException("Missing required data: " . implode(', ', $validationResult['missing']));
        }
        
        // Process templates
        $systemPrompt = $this->templateProcessor->process($process->system_prompt, $data);
        $userPrompt = $overrideUserPrompt ?: $this->templateProcessor->process($process->user_prompt, $data);
        
        // Create interaction record
        $interaction = LLMInteraction::create([
            'process_id' => $process->id,
            'model_type' => $process->model_class,
            'model_id' => $modelId,
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
            'attachments' => [],
            'options' => [
                'model' => $process->model,
                'temperature' => $process->temperature,
                'max_tokens' => $process->max_output_tokens,
                'output_type' => $process->output_type,
                'structured_output_schema' => $process->structured_output_schema,
                'use_web_search' => $process->use_web_search,
                'use_reasoning' => $process->use_reasoning,
            ],
            'status' => 'pending',
        ]);
        
        // Dispatch job or process synchronously
        if ($async) {
            ProcessLLMInferenceJob::dispatch($interaction->id);
        } else {
            // Process synchronously
            $this->processInteraction($interaction);
        }
        
        // Return interaction for polling
        return $interaction;
    }

    /**
     * Load model with dependencies.
     *
     * @param LLMProcess $process
     * @param string $modelId
     * @return Model|null
     */
    private function loadModelWithDependencies(LLMProcess $process, string $modelId): ?Model
    {
        return $process->getModelInstance($modelId);
    }

    /**
     * Prepare data from model.
     *
     * @param LLMProcess $process
     * @param Model $model
     * @return array
     */
    private function prepareData(LLMProcess $process, Model $model): array
    {
        // Convert model to array
        $modelData = $model->toArray();
        
        // Flatten to dot notation
        $flattenedData = $this->templateProcessor->flattenArray($modelData);
        
        return $flattenedData;
    }

    /**
     * Validate data requirements.
     *
     * @param LLMProcess $process
     * @param array $data
     * @return array
     */
    private function validateDataRequirements(LLMProcess $process, array $data): array
    {
        // Extract variables from prompts
        $systemVariables = $this->templateProcessor->extractVariables($process->system_prompt);
        $userVariables = $this->templateProcessor->extractVariables($process->user_prompt);
        
        // Combine variables
        $variables = array_unique(array_merge($systemVariables, $userVariables));
        
        // Check for missing/null values
        $missing = [];
        foreach ($variables as $variable) {
            if (!isset($data[$variable]) || $data[$variable] === null || $data[$variable] === '') {
                $missing[] = $variable;
            }
        }
        
        return [
            'valid' => empty($missing),
            'missing' => $missing
        ];
    }

    /**
     * Process interaction synchronously.
     *
     * @param LLMInteraction $interaction
     * @return void
     */
    private function processInteraction(LLMInteraction $interaction): void
    {
        // Update status to processing
        $interaction->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            // Load process
            $process = $interaction->process;
            
            // Load model with dependencies
            $model = $this->loadModelWithDependencies($process, $interaction->model_id);
            
            if (!$model) {
                throw new ModelNotFoundException("Model {$interaction->model_type} with ID {$interaction->model_id} not found");
            }
            
            // Flatten model data to array
            $data = $this->prepareData($process, $model);
            
            // Process templates with TemplateProcessor
            $systemPrompt = $this->templateProcessor->process($interaction->system_prompt, $data);
            $userPrompt = $this->templateProcessor->process($interaction->user_prompt, $data);
            
            // Resolve attachments with AttachmentResolver
            $attachments = [];
            if (!empty($process->attachments)) {
                $attachments = $this->attachmentResolver->resolve($process->attachments, $data);
            }
            
            // Update interaction with attachments
            $interaction->update(['attachments' => $attachments]);
            
            // Build OpenRouter request
            $messages = $this->openRouterClient->formatMessages($systemPrompt, $userPrompt, $attachments);
            
            // Send request via OpenRouterClient
            $response = $this->openRouterClient->chat($messages, [
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
                    'model' => $response->model,
                    'tokens_used' => $response->tokens_used ?? null,
                    'cost' => $response->cost ?? null,
                ],
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Update interaction status to failed
            $interaction->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            
            throw $e;
        }
    }
}