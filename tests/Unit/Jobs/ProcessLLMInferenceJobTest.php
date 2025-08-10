<?php

namespace Lanos\LLMProcessor\Tests\Unit\Jobs;

use Lanos\LLMProcessor\Jobs\ProcessLLMInferenceJob;
use Lanos\LLMProcessor\Models\LLMProcess;
use Lanos\LLMProcessor\Models\LLMInteraction;
use Lanos\LLMProcessor\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class ProcessLLMInferenceJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_created_with_interaction_id()
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

        // Create the job
        $job = new ProcessLLMInferenceJob($interaction->id);

        // Assertions
        $this->assertEquals($interaction->id, $job->interactionId);
    }

    /** @test */
    public function it_has_correct_timeout()
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

        // Create the job
        $job = new ProcessLLMInferenceJob($interaction->id);

        // Assertions
        $this->assertEquals(60, $job->timeout);
    }

    /** @test */
    public function it_can_be_dispatched()
    {
        // Fake the queue
        Queue::fake();

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

        // Dispatch the job
        ProcessLLMInferenceJob::dispatch($interaction->id);

        // Assert the job was pushed to the queue
        Queue::assertPushed(ProcessLLMInferenceJob::class);
    }
}