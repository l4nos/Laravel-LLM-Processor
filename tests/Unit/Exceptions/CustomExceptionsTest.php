<?php

namespace Lanos\LLMProcessor\Tests\Unit\Exceptions;

use Lanos\LLMProcessor\Exceptions\ModelNotFoundException;
use Lanos\LLMProcessor\Exceptions\MissingDataException;
use Lanos\LLMProcessor\Exceptions\LLMProcessException;
use Lanos\LLMProcessor\Tests\TestCase;

class CustomExceptionsTest extends TestCase
{
    /** @test */
    public function it_can_create_model_not_found_exception()
    {
        $exception = new ModelNotFoundException('Model not found');

        $this->assertInstanceOf(ModelNotFoundException::class, $exception);
        $this->assertEquals('Model not found', $exception->getMessage());
    }

    /** @test */
    public function it_can_create_missing_data_exception()
    {
        $exception = new MissingDataException('Missing required data');

        $this->assertInstanceOf(MissingDataException::class, $exception);
        $this->assertEquals('Missing required data', $exception->getMessage());
    }

    /** @test */
    public function it_can_create_llm_process_exception()
    {
        $exception = new LLMProcessException('LLM process error');

        $this->assertInstanceOf(LLMProcessException::class, $exception);
        $this->assertEquals('LLM process error', $exception->getMessage());
    }
}