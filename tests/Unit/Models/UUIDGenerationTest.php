<?php

namespace Lanos\LLMProcessor\Tests\Unit\Models;

use Lanos\LLMProcessor\Models\LLMProcess;
use Lanos\LLMProcessor\Tests\TestCase;

class UUIDGenerationTest extends TestCase
{
    /** @test */
    public function it_generates_uuid_for_process()
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

        // Debug output
        \Log::info('Process ID: ' . $process->id);
        \Log::info('Process ID type: ' . gettype($process->id));

        // Assertions
        $this->assertNotNull($process->id);
        $this->assertIsString($process->id);
        $this->assertNotEquals(1, $process->id);
        $this->assertNotEquals('1', $process->id);
    }
}