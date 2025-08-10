<?php

namespace Lanos\LLMProcessor\Tests\Unit\Services;

use Lanos\LLMProcessor\Models\LLMProcess;
use Lanos\LLMProcessor\Models\LLMInteraction;
use Lanos\LLMProcessor\Services\LLMInferenceService;
use Lanos\LLMProcessor\Services\TemplateProcessor;
use Lanos\LLMProcessor\Services\OpenRouterClient;
use Lanos\LLMProcessor\Services\AttachmentResolver;
use Lanos\LLMProcessor\Tests\TestCase;

class LLMInferenceServiceTest extends TestCase
{

    /**
     * @var LLMInferenceService
     */
    protected $service;

    /**
     * @var \Mockery\MockInterface
     */
    protected $templateProcessor;

    /**
     * @var \Mockery\MockInterface
     */
    protected $openRouterClient;

    /**
     * @var \Mockery\MockInterface
     */
    protected $attachmentResolver;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for dependencies
        $this->templateProcessor = \Mockery::mock(TemplateProcessor::class);
        $this->openRouterClient = \Mockery::mock(OpenRouterClient::class);
        $this->attachmentResolver = \Mockery::mock(AttachmentResolver::class);

        // Create the service with mocked dependencies
        $this->service = new LLMInferenceService(
            $this->templateProcessor,
            $this->openRouterClient,
            $this->attachmentResolver
        );
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(LLMInferenceService::class, $this->service);
    }

    /** @test */
    public function it_can_process_inference_async()
    {
        // Create a test model instance
        $testModel = \Lanos\LLMProcessor\Tests\TestModel::create([
            'name' => 'Test Model',
            'description' => 'A test model for testing',
        ]);

        // Create a test process
        $process = LLMProcess::create([
            'name' => 'Test Process',
            'slug' => 'test-process',
            'model_class' => 'Lanos\LLMProcessor\Tests\TestModel',
            'system_prompt' => 'You are a helpful assistant.',
            'user_prompt' => 'Analyze this data: {{data}}',
            'model' => 'openai/gpt-4',
        ]);

        // Mock the template processor
        $this->templateProcessor->shouldReceive('flattenArray')
            ->twice()
            ->andReturn(['data' => 'test']);

        $this->templateProcessor->shouldReceive('process')
            ->times(4)
            ->andReturn('Processed prompt');
        
        $this->templateProcessor->shouldReceive('extractVariables')
            ->twice()
            ->andReturn([]);

        // Mock the attachment resolver
        $this->attachmentResolver->shouldReceive('resolve')
            ->andReturn([]);

        // Mock the OpenRouter client
        $response = new \stdClass();
        $response->content = 'Test response';
        $response->model = 'openai/gpt-4';
        $this->openRouterClient->shouldReceive('formatMessages')
            ->andReturn([]);
        $this->openRouterClient->shouldReceive('chat')
            ->andReturn($response);

        // Process the inference
        $interaction = $this->service->processInference($process->id, $testModel->id, [], false);

        // Assertions
        $this->assertInstanceOf(LLMInteraction::class, $interaction);
        $this->assertEquals('completed', $interaction->status);
        $this->assertEquals($process->id, $interaction->process_id);
    }

    /** @test */
    public function it_can_process_inference_sync()
    {
        // Create a test model instance
        $testModel = \Lanos\LLMProcessor\Tests\TestModel::create([
            'name' => 'Test Model',
            'description' => 'A test model for testing',
        ]);

        // Create a test process
        $process = LLMProcess::create([
            'name' => 'Test Process',
            'slug' => 'test-process',
            'model_class' => 'Lanos\LLMProcessor\Tests\TestModel',
            'system_prompt' => 'You are a helpful assistant.',
            'user_prompt' => 'Analyze this data: {{data}}',
            'model' => 'openai/gpt-4',
        ]);

        // Mock the template processor
        $this->templateProcessor->shouldReceive('flattenArray')
            ->twice()
            ->andReturn(['data' => 'test']);

        $this->templateProcessor->shouldReceive('process')
            ->times(4)
            ->andReturn('Processed prompt');
        
        $this->templateProcessor->shouldReceive('extractVariables')
            ->twice()
            ->andReturn([]);

        // Mock the attachment resolver
        $this->attachmentResolver->shouldReceive('resolve')
            ->andReturn([]);

        // Mock the OpenRouter client
        $response = new \stdClass();
        $response->content = 'Test response';
        $response->model = 'openai/gpt-4';
        $this->openRouterClient->shouldReceive('formatMessages')
            ->andReturn([]);
        $this->openRouterClient->shouldReceive('chat')
            ->andReturn($response);

        // Process the inference synchronously
        $interaction = $this->service->processInference($process->id, $testModel->id, [], false);

        // Assertions
        $this->assertInstanceOf(LLMInteraction::class, $interaction);
        $this->assertEquals($process->id, $interaction->process_id);
    }
}