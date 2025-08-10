# Laravel LLM Processor

A Laravel package that enables LLM processing for any Eloquent model without requiring modifications to those models. The package allows defining LLM processes that specify which model and relations to work with, then processes data from those models through AI prompts.

## Installation

You can install the package via composer:

```bash
composer require lanos/llm-processor
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag=llm-processor-migrations
php artisan migrate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=llm-processor-config
```

## Configuration

The package can be configured by editing the `config/llm-processor.php` configuration file. You can set your OpenRouter API key and other settings:

```php
return [
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        // ... other settings
    ],
    // ... other configuration options
];
```

Don't forget to add your OpenRouter API key to your `.env` file:

```env
OPENROUTER_API_KEY=your-api-key-here
```

## Usage

### Creating an LLM Process

First, create an LLM process that defines how to process your models:

```php
use Lanos\LLMProcessor\Models\LLMProcess;

$process = LLMProcess::create([
    'name' => 'Extract Property Insights',
    'slug' => 'property-insights',
    'model_class' => 'App\Models\Property',
    'dependencies' => ['images', 'owner.profile', 'listings'],
    'system_prompt' => 'You are analyzing property {{id}} located at {{address}}',
    'user_prompt' => 'Analyse {{description}} and images at {{images.0.url}}',
    'model' => 'openai/gpt-4',
    'temperature' => 0.7,
    'max_output_tokens' => 4096,
    'is_active' => true,
]);
```

### Processing a Model

To process a model with an LLM process, use the facade:

```php
use Lanos\LLMProcessor\Facades\LLMProcessor;

// Process asynchronously (default)
$interaction = LLMProcessor::process($process->id, $propertyId);

// Process synchronously
$interaction = LLMProcessor::processSync($process->id, $propertyId);
```

### Checking Process Status

You can check the status of an interaction:

```php
$interaction = LLMProcessor::getInteraction($interactionId);

if ($interaction->status === 'completed') {
    echo $interaction->response;
}
```

## Advanced Usage

### Using Attachments

You can specify attachments in your process configuration:

```php
$process = LLMProcess::create([
    // ... other fields
    'attachments' => ['images.0.url', 'documents.0.file_url'],
]);
```

### Structured Output

You can request structured JSON output:

```php
$process = LLMProcess::create([
    // ... other fields
    'output_type' => 'json',
    'structured_output_schema' => [
        'name' => 'property_analysis',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'property_value' => ['type' => 'string'],
                'recommendations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ]
            ],
            'required' => ['property_value', 'recommendations']
        ]
    ],
]);
```

### Web Search and Reasoning

Enable web search and reasoning capabilities:

```php
$process = LLMProcess::create([
    // ... other fields
    'use_web_search' => true,
    'use_reasoning' => true,
]);
```

## Testing

To run the tests:

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [L4nos](https://github.com/l4nos)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.