<?php

namespace Lanos\LLMProcessor\Facades;

use Illuminate\Support\Facades\Facade;
use Lanos\LLMProcessor\Models\LLMProcess;
use Lanos\LLMProcessor\Models\LLMInteraction;

/**
 * @method static LLMInteraction process(string $processId, string $modelId, bool $async = true)
 * @method static LLMInteraction processSync(string $processId, string $modelId)
 * @method static LLMProcess|null getProcess(string $slug)
 * @method static LLMInteraction|null getInteraction(string $id)
 */
class LLMProcessor extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'llm-processor';
    }
}