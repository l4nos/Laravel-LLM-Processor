<?php

namespace Lanos\LLMProcessor\Services;

use Illuminate\Support\Facades\Http;
use Lanos\LLMProcessor\Models\LLMProcess;
use stdClass;

class OpenRouterClient
{
    /**
     * Base URL for the OpenRouter API.
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * API key for the OpenRouter API.
     *
     * @var string|null
     */
    protected ?string $apiKey;

    /**
     * Timeout for requests.
     *
     * @var int
     */
    protected int $timeout;

    /**
     * Number of retry attempts.
     *
     * @var int
     */
    protected int $retryTimes;

    /**
     * Delay between retries in milliseconds.
     *
     * @var int
     */
    protected int $retryDelay;

    /**
     * Create a new OpenRouterClient instance.
     */
    public function __construct()
    {
        $this->baseUrl = config('llm-processor.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->apiKey = config('llm-processor.openrouter.api_key');
        $this->timeout = config('llm-processor.openrouter.timeout', 60);
        $this->retryTimes = config('llm-processor.openrouter.retry_times', 3);
        $this->retryDelay = config('llm-processor.openrouter.retry_delay', 1000);
    }

    /**
     * Send chat request to OpenRouter.
     *
     * @param array $messages
     * @param array $options
     * @return object
     * @throws \Exception
     */
    public function chat(array $messages, array $options = []): object
    {
        $payload = $this->buildRequestPayload($messages, $options);
        
        $attempt = 0;
        $lastException = null;
        
        while ($attempt <= $this->retryTimes) {
            try {
                $headers = [
                    'HTTP-Referer' => url('/'),
                    'X-Title' => config('app.name', 'Laravel'),
                ];
                
                // Only add Authorization header if API key is set
                if (!empty($this->apiKey)) {
                    $headers['Authorization'] = 'Bearer ' . $this->apiKey;
                }
                
                $response = Http::withHeaders($headers)
                    ->timeout($this->timeout)
                    ->post($this->baseUrl . '/chat/completions', $payload);
                
                if ($response->successful()) {
                    $responseData = $response->json();
                    return $this->formatResponse($responseData);
                } else {
                    throw new \Exception('OpenRouter API request failed: ' . $response->body());
                }
            } catch (\Exception $e) {
                $lastException = $e;
                
                if ($attempt < $this->retryTimes) {
                    usleep($this->retryDelay * 1000); // Convert to microseconds
                    $attempt++;
                    continue;
                }
                
                throw $e;
            }
        }
        
        throw $lastException;
    }

    /**
     * Format messages for the OpenRouter API.
     *
     * @param string $systemPrompt
     * @param string $userPrompt
     * @param array $attachments
     * @return array
     */
    public function formatMessages(string $systemPrompt, string $userPrompt, array $attachments = []): array
    {
        $messages = [];
        
        if (!empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }
        
        $content = [];
        
        if (!empty($userPrompt)) {
            $content[] = [
                'type' => 'text',
                'text' => $userPrompt
            ];
        }
        
        // Add attachments
        foreach ($attachments as $attachment) {
            $content[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $attachment['url']
                ]
            ];
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $content
        ];
        
        return $messages;
    }

    /**
     * Build request payload for the OpenRouter API.
     *
     * @param array $messages
     * @param array $options
     * @return array
     */
    public function buildRequestPayload(array $messages, array $options = []): array
    {
        $payload = [
            'messages' => $messages,
        ];
        
        // Add model configuration
        if (isset($options['model'])) {
            $payload['model'] = $options['model'];
        }
        
        // Add temperature
        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }
        
        // Add max tokens
        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }
        
        // Add structured output schema if needed
        if (isset($options['structured_output_schema']) && !empty($options['structured_output_schema'])) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => $options['structured_output_schema']
            ];
        }
        
        // Add plugins (web search) if needed
        if (isset($options['use_web_search']) && $options['use_web_search']) {
            $payload['tools'] = [
                ['type' => 'web_search']
            ];
        }
        
        // Add reasoning if needed
        if (isset($options['use_reasoning']) && $options['use_reasoning']) {
            $payload['reasoning'] = true;
        }
        
        return $payload;
    }

    /**
     * Format response from OpenRouter API.
     *
     * @param array $responseData
     * @return object
     */
    private function formatResponse(array $responseData): object
    {
        $response = new stdClass();
        
        if (isset($responseData['choices'][0]['message']['content'])) {
            $response->content = $responseData['choices'][0]['message']['content'];
        } else {
            $response->content = '';
        }
        
        if (isset($responseData['model'])) {
            $response->model = $responseData['model'];
        }
        
        if (isset($responseData['usage'])) {
            $response->tokens_used = $responseData['usage'];
        }
        
        return $response;
    }
}