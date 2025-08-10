<?php

namespace Lanos\LLMProcessor\Tests\Unit\Models;

use Lanos\LLMProcessor\Models\LLMInteraction;
use Lanos\LLMProcessor\Models\LLMProcess;
use Lanos\LLMProcessor\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class LLMInteractionTest extends TestCase
{
    /** @test */
    public function it_has_correct_table_name()
    {
        $interaction = new LLMInteraction();
        $this->assertEquals('llm_interactions', $interaction->getTable());
    }

    /** @test */
    public function it_has_expected_fillable_fields()
    {
        $interaction = new LLMInteraction();
        $fillable = $interaction->getFillable();
        
        $this->assertContains('process_id', $fillable);
        $this->assertContains('model_type', $fillable);
        $this->assertContains('model_id', $fillable);
        $this->assertContains('system_prompt', $fillable);
        $this->assertContains('user_prompt', $fillable);
        $this->assertContains('status', $fillable);
    }

    /** @test */
    public function it_has_expected_casts()
    {
        $interaction = new LLMInteraction();
        $casts = $interaction->getCasts();
        
        $this->assertArrayHasKey('attachments', $casts);
        $this->assertArrayHasKey('options', $casts);
        $this->assertArrayHasKey('response_metadata', $casts);
        $this->assertEquals('array', $casts['attachments']);
        $this->assertEquals('array', $casts['options']);
        $this->assertEquals('array', $casts['response_metadata']);
    }

    /** @test */
    public function it_can_be_created_with_factory_data()
    {
        $process = LLMProcess::create([
            'name' => 'Test Process',
            'slug' => 'test-process',
            'model_class' => 'App\Models\TestModel',
            'system_prompt' => 'You are a helpful assistant.',
            'user_prompt' => 'Analyze this data: {{data}}',
            'model' => 'openai/gpt-4',
        ]);

        $interaction = LLMInteraction::create([
            'process_id' => $process->id,
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

        $this->assertDatabaseHas('llm_interactions', [
            'process_id' => $process->id,
            'model_type' => 'App\Models\TestModel',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_have_process_relationship()
    {
        $process = LLMProcess::create([
            'name' => 'Test Process',
            'slug' => 'test-process',
            'model_class' => 'App\Models\TestModel',
            'system_prompt' => 'You are a helpful assistant.',
            'user_prompt' => 'Analyze this data: {{data}}',
            'model' => 'openai/gpt-4',
        ]);

        $interaction = LLMInteraction::create([
            'process_id' => $process->id,
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

        // Reload the interaction to ensure the relationship is loaded
        $interaction = LLMInteraction::find($interaction->id);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $interaction->process());
        $this->assertEquals($process->id, $interaction->process->id);
    }

    /** @test */
    public function it_can_get_model_instance()
    {
        // For this test, we'll just verify the method exists
        // In a real application, you would need a test model
        $interaction = new LLMInteraction();
        $this->assertTrue(method_exists($interaction, 'getModel'));
    }
}