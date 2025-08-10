<?php

namespace Lanos\LLMProcessor\Tests\Unit\Services;

use Lanos\LLMProcessor\Services\OpenRouterClient;
use Lanos\LLMProcessor\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class OpenRouterClientTest extends TestCase
{
    /**
     * @var OpenRouterClient
     */
    protected $openRouterClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->openRouterClient = new OpenRouterClient();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(OpenRouterClient::class, $this->openRouterClient);
    }

    /** @test */
    public function it_can_format_messages()
    {
        $systemPrompt = 'You are a helpful assistant.';
        $userPrompt = 'Analyze this data: test data';
        $attachments = [];

        $messages = $this->openRouterClient->formatMessages($systemPrompt, $userPrompt, $attachments);

        $this->assertCount(2, $messages);
        $this->assertEquals('system', $messages[0]['role']);
        $this->assertEquals('system', $messages[0]['role']);
        $this->assertEquals($systemPrompt, $messages[0]['content']);
        $this->assertEquals('user', $messages[1]['role']);
        $this->assertEquals($userPrompt, $messages[1]['content'][0]['text']);
    }

    /** @test */
    public function it_can_format_messages_with_attachments()
    {
        $systemPrompt = 'You are a helpful assistant.';
        $userPrompt = 'Analyze this data: test data';
        $attachments = [
            ['url' => 'https://example.com/image.jpg']
        ];

        $messages = $this->openRouterClient->formatMessages($systemPrompt, $userPrompt, $attachments);

        $this->assertCount(2, $messages);
        $this->assertEquals('user', $messages[1]['role']);
        $this->assertCount(2, $messages[1]['content']);
        $this->assertEquals('text', $messages[1]['content'][0]['type']);
        $this->assertEquals('image_url', $messages[1]['content'][1]['type']);
    }

    /** @test */
    public function it_can_build_request_payload()
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Analyze this data: test data']
        ];

        $options = [
            'model' => 'openai/gpt-4',
            'temperature' => 0.7,
            'max_tokens' => 4096,
        ];

        $payload = $this->openRouterClient->buildRequestPayload($messages, $options);

        $this->assertArrayHasKey('messages', $payload);
        $this->assertArrayHasKey('model', $payload);
        $this->assertArrayHasKey('temperature', $payload);
        $this->assertArrayHasKey('max_tokens', $payload);
        $this->assertEquals('openai/gpt-4', $payload['model']);
        $this->assertEquals(0.7, $payload['temperature']);
        $this->assertEquals(4096, $payload['max_tokens']);
    }

    /** @test */
    public function it_can_build_request_payload_with_structured_output()
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Analyze this data: test data']
        ];

        $options = [
            'model' => 'openai/gpt-4',
            'structured_output_schema' => [
                'name' => 'test_schema',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string']
                    ]
                ]
            ]
        ];

        $payload = $this->openRouterClient->buildRequestPayload($messages, $options);

        $this->assertArrayHasKey('response_format', $payload);
        $this->assertEquals('json_schema', $payload['response_format']['type']);
    }

    /** @test */
    public function it_can_build_request_payload_with_web_search()
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Analyze this data: test data']
        ];

        $options = [
            'model' => 'openai/gpt-4',
            'use_web_search' => true
        ];

        $payload = $this->openRouterClient->buildRequestPayload($messages, $options);

        $this->assertArrayHasKey('tools', $payload);
        $this->assertEquals('web_search', $payload['tools'][0]['type']);
    }
}