<?php

namespace Lanos\LLMProcessor\Tests\Feature;

use Lanos\LLMProcessor\Facades\LLMProcessor;
use Lanos\LLMProcessor\Models\LLMProcess;
use Lanos\LLMProcessor\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LLMProcessorFacadeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_process_by_slug()
    {
        // Create a test process
        $process = LLMProcess::create([
            'name' => 'Test Process',
            'slug' => 'test-process',
            'model_class' => 'App\Models\TestModel',
            'system_prompt' => 'You are a helpful assistant.',
            'user_prompt' => 'Analyze this data: {{data}}',
            'model' => 'openai/gpt-4',
        ]);

        // Get the process using the facade
        $retrievedProcess = LLMProcessor::getProcess('test-process');

        // Assertions
        $this->assertNotNull($retrievedProcess);
        $this->assertEquals($process->id, $retrievedProcess->id);
        $this->assertEquals('Test Process', $retrievedProcess->name);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_process()
    {
        // Try to get a process that doesn't exist
        $process = LLMProcessor::getProcess('nonexistent-process');

        // Assertions
        $this->assertNull($process);
    }

    /** @test */
    public function it_can_get_interaction_by_id()
    {
        // Create a test process
        $process = LLMProcess::create([
            'name' => 'Test Process',
            'slug' => 'test-process',
            'model_class' => 'App\Models\TestModel',
            'system_prompt' => 'You are a helpful assistant.',
            'user_prompt' => 'Analyze this data: {{data}}',
            'model' => 'openai/gpt-4',
        ]);

        // Create a test interaction
        $interaction = $process->interactions()->create([
            'model_type' => 'App\Models\TestModel',
            'model_id' => '1',
            'system_prompt' => 'You are a helpful assistant.',
            'user_prompt' => 'Analyze this data: {{data}}',
            'options' => [
                'model' => 'openai/gpt-4',
                'temperature' => 0.7,
            ],
            'status' => 'pending',
        ]);

        // Get the interaction using the facade
        $retrievedInteraction = LLMProcessor::getInteraction($interaction->id);

        // Assertions
        $this->assertNotNull($retrievedInteraction);
        $this->assertEquals($interaction->id, $retrievedInteraction->id);
        $this->assertEquals('pending', $retrievedInteraction->status);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_interaction()
    {
        // Try to get an interaction that doesn't exist
        $interaction = LLMProcessor::getInteraction('nonexistent-id');

        // Assertions
        $this->assertNull($interaction);
    }
}