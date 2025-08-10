<?php

namespace Lanos\LLMProcessor\Tests\Unit\Services;

use Lanos\LLMProcessor\Services\TemplateProcessor;
use Lanos\LLMProcessor\Tests\TestCase;

class TemplateProcessorTest extends TestCase
{
    /**
     * @var TemplateProcessor
     */
    protected $templateProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateProcessor = new TemplateProcessor();
    }

    /** @test */
    public function it_can_process_templates_with_variables()
    {
        $template = 'Hello {{name}}, you are {{age}} years old.';
        $data = [
            'name' => 'John',
            'age' => 30
        ];

        $result = $this->templateProcessor->process($template, $data);

        $this->assertEquals('Hello John, you are 30 years old.', $result);
    }

    /** @test */
    public function it_can_process_templates_with_dot_notation()
    {
        $template = 'Hello {{user.name}}, you live at {{user.address.city}}.';
        $data = [
            'user' => [
                'name' => 'John',
                'address' => [
                    'city' => 'New York'
                ]
            ]
        ];

        $result = $this->templateProcessor->process($template, $data);

        $this->assertEquals('Hello John, you live at New York.', $result);
    }

    /** @test */
    public function it_handles_missing_variables_with_placeholder()
    {
        $template = 'Hello {{name}}, you are {{age}} years old.';
        $data = [
            'name' => 'John'
            // age is missing
        ];

        $result = $this->templateProcessor->process($template, $data);

        $this->assertEquals('Hello John, you are  years old.', $result);
    }

    /** @test */
    public function it_can_extract_variables_from_template()
    {
        $template = 'Hello {{name}}, you are {{age}} years old and live in {{location}}.';

        $variables = $this->templateProcessor->extractVariables($template);

        $this->assertCount(3, $variables);
        $this->assertContains('name', $variables);
        $this->assertContains('age', $variables);
        $this->assertContains('location', $variables);
    }

    /** @test */
    public function it_can_flatten_arrays_to_dot_notation()
    {
        $array = [
            'user' => [
                'name' => 'John',
                'address' => [
                    'city' => 'New York',
                    'zip' => '10001'
                ]
            ],
            'age' => 30
        ];

        $flattened = $this->templateProcessor->flattenArray($array);

        $this->assertCount(4, $flattened);
        $this->assertEquals('John', $flattened['user.name']);
        $this->assertEquals('New York', $flattened['user.address.city']);
        $this->assertEquals('10001', $flattened['user.address.zip']);
        $this->assertEquals(30, $flattened['age']);
    }
}