<?php

namespace Lanos\LLMProcessor\Tests\Unit\Models;

use Lanos\LLMProcessor\Models\LLMProcess;
use Lanos\LLMProcessor\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class LLMProcessTest extends TestCase
{
    /** @test */
    public function it_has_correct_table_name()
    {
        $process = new LLMProcess();
        $this->assertEquals('llm_processes', $process->getTable());
    }

    /** @test */
    public function it_has_expected_fillable_fields()
    {
        $process = new LLMProcess();
        $fillable = $process->getFillable();
        
        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('model_class', $fillable);
        $this->assertContains('system_prompt', $fillable);
        $this->assertContains('user_prompt', $fillable);
    }

    /** @test */
    public function it_has_expected_casts()
    {
        $process = new LLMProcess();
        $casts = $process->getCasts();
        
        $this->assertArrayHasKey('dependencies', $casts);
        $this->assertArrayHasKey('structured_output_schema', $casts);
        $this->assertArrayHasKey('attachments', $casts);
        $this->assertArrayHasKey('temperature', $casts);
        $this->assertEquals('array', $casts['dependencies']);
        $this->assertEquals('array', $casts['structured_output_schema']);
        $this->assertEquals('array', $casts['attachments']);
        $this->assertEquals('decimal:2', $casts['temperature']);
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
            'temperature' => 0.7,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('llm_processes', [
            'name' => 'Test Process',
            'slug' => 'test-process',
        ]);
    }

    /** @test */
    public function it_can_have_interactions_relationship()
    {
        $process = LLMProcess::create([
            'name' => 'Test Process',
            'slug' => 'test-process',
            'model_class' => 'App\Models\TestModel',
            'system_prompt' => 'You are a helpful assistant.',
            'user_prompt' => 'Analyze this data: {{data}}',
            'model' => 'openai/gpt-4',
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\HasMany', $process->interactions());
    }
}