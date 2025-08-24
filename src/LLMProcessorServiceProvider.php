<?php

namespace Lanos\LLMProcessor;

use Illuminate\Support\ServiceProvider;
use Lanos\LLMProcessor\Models\LLMProcess;
use Lanos\LLMProcessor\Models\LLMInteraction;
use Lanos\LLMProcessor\Services\LLMInferenceService;
use Lanos\LLMProcessor\Services\TemplateProcessor;
use Lanos\LLMProcessor\Services\OpenRouterClient;
use Lanos\LLMProcessor\Services\AttachmentResolver;

class LLMProcessorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/llm-processor.php', 'llm-processor'
        );

        // Register services
        $this->app->singleton(LLMInferenceService::class, function ($app) {
            return new LLMInferenceService(
                $app->make(TemplateProcessor::class),
                $app->make(OpenRouterClient::class),
                $app->make(AttachmentResolver::class)
            );
        });

        $this->app->singleton(TemplateProcessor::class, function ($app) {
            return new TemplateProcessor();
        });

        $this->app->singleton(OpenRouterClient::class, function ($app) {
            return new OpenRouterClient();
        });

        $this->app->singleton(AttachmentResolver::class, function ($app) {
            return new AttachmentResolver();
        });

        // Register facade
        $this->app->bind('llm-processor', function ($app) {
            return new class {
                public function process(string $processId, string $modelId, string | false $overrideUserPrompt = false)
                {
                    $service = app(LLMInferenceService::class);
                    return $service->processInference($processId, $modelId, [], true, $overrideUserPrompt);
                }

                public function processSync(string $processId, string $modelId, string | false $overrideUserPrompt = false)
                {
                    $service = app(LLMInferenceService::class);
                    return $service->processInference($processId, $modelId, [], false, $overrideUserPrompt);
                }

                public function getProcess(string $slug): ?LLMProcess
                {
                    return LLMProcess::where('slug', $slug)->first();
                }

                public function getInteraction(string $id): ?LLMInteraction
                {
                    return LLMInteraction::find($id);
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/llm-processor.php' => config_path('llm-processor.php'),
        ], 'llm-processor-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'llm-processor-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}