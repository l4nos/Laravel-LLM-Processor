# Laravel LLM Processor Package - Implementation Specification

## Package Purpose

Build a Laravel package that enables LLM processing for any Eloquent model without requiring modifications to those models. The package allows defining LLM processes that specify which model and relations to work with, then processes data from those models through AI prompts.

Package name Laravel LLM Processor
Namespace: Lanos\LLMProcessor
Author: L4nos
email: rob@updev.agency
github: l4nos

## Core Architecture

### Package Structure
```
llm-processor/
├── config/
│   └── llm-processor.php
├── database/
│   └── migrations/
│       ├── create_llm_processes_table.php
│       └── create_llm_interactions_table.php
├── src/
│   ├── LLMProcessorServiceProvider.php
│   ├── Models/
│   │   ├── LLMProcess.php
│   │   └── LLMInteraction.php
│   ├── Services/
│   │   ├── LLMInferenceService.php
│   │   ├── TemplateProcessor.php
│   │   ├── OpenRouterClient.php
│   │   └── AttachmentResolver.php
│   ├── Jobs/
│   │   └── ProcessLLMInferenceJob.php
│   ├── Exceptions/
│   │   ├── ModelNotFoundException.php
│   │   ├── MissingDataException.php
│   │   └── LLMProcessException.php
│   └── Facades/
│       └── LLMProcessor.php
```

## Component Specifications

### 1. Models

#### LLMProcess Model
Database fields:
- `id` - UUID primary key
- `name` - Human readable name
- `slug` - Unique identifier for the process
- `description` - Optional description
- `model_class` - Fully qualified class name (e.g., 'App\Models\Project')
- `dependencies` - JSON array of relations to eager load (e.g., ['property', 'client.contacts'])
- `system_prompt` - System prompt template with merge variables
- `user_prompt` - User prompt template with merge variables
- `model` - OpenRouter model identifier
- `temperature` - Temperature setting (0-2)
- `max_output_tokens` - Maximum tokens for response
- `output_type` - 'text' or 'json'
- `structured_output_schema` - JSON schema for structured outputs (nullable)
- `attachments` - JSON array of dot-notation paths to file URLs in the model data
- `terminate_on_missing_data` - Boolean, whether to fail if variables are missing
- `use_web_search` - Boolean for OpenRouter web plugin
- `use_reasoning` - Boolean for OpenRouter reasoning
- `is_active` - Boolean to enable/disable process
- `metadata` - JSON field for any additional configuration
- `created_at`, `updated_at`, `deleted_at` - Timestamps with soft delete

Methods needed:
- `validate()` - Validate the process configuration
- `getModelInstance($id)` - Load model instance with dependencies
- `interactions()` - HasMany relationship

#### LLMInteraction Model
Database fields:
- `id` - UUID primary key
- `process_id` - Foreign key to LLMProcess
- `model_type` - Class name of the processed model
- `model_id` - ID of the processed model instance
- `system_prompt` - Actual system prompt after variable substitution
- `user_prompt` - Actual user prompt after variable substitution
- `attachments` - JSON array of attachments actually sent
- `options` - JSON object of options used (model, temperature, etc.)
- `status` - Enum: 'pending', 'processing', 'completed', 'failed'
- `response` - Text response from LLM
- `response_metadata` - JSON metadata (tokens used, model, cost, etc.)
- `error_message` - Error details if failed
- `started_at` - When processing started
- `completed_at` - When processing completed
- `created_at`, `updated_at` - Timestamps

Methods needed:
- `process()` - BelongsTo relationship
- `getModel()` - Retrieve the original model instance

### 2. Services

#### LLMInferenceService
Main orchestration service.

Public methods:
- `processInference(string $processId, string $modelId, array $context = [], bool $async = true): LLMInteraction`
  - Load the LLMProcess
  - Load the model instance with dependencies
  - Process templates
  - Create interaction record
  - Dispatch job or process synchronously
  - Return interaction for polling

Private methods:
- `loadModelWithDependencies(LLMProcess $process, string $modelId)`
  - Instantiate model class
  - Load specified relations
  - Return loaded model

- `prepareData(LLMProcess $process, $model): array`
  - Convert model to array
  - Flatten to dot notation
  - Merge with any additional context
  - Return flattened array

- `validateDataRequirements(LLMProcess $process, array $data): array`
  - Extract variables from prompts
  - Check for missing/null values
  - Return validation results

#### TemplateProcessor
Handle template variable substitution.

Methods:
- `process(string $template, array $data): string`
  - Replace {{variables}} with values from data
  - Support dot notation
  - Handle missing variables based on configuration

- `extractVariables(string $template): array`
  - Find all {{variable}} patterns
  - Return list of variable names

- `flattenArray(array $array, string $prefix = ''): array`
  - Recursively flatten nested arrays
  - Use dot notation for keys
  - Handle various data types appropriately

#### OpenRouterClient
HTTP client for OpenRouter API.

Methods:
- `chat(array $messages, array $options = []): object`
  - Format request for OpenRouter
  - Handle retries
  - Return standardised response

- `formatMessages(string $systemPrompt, string $userPrompt, array $attachments = []): array`
  - Build messages array
  - Handle attachment formatting
  - Support image data URLs

- `buildRequestPayload(array $messages, LLMProcess $process): array`
  - Add model configuration
  - Add structured output schema if needed
  - Add plugins (web search) if needed
  - Add reasoning if needed

#### AttachmentResolver
Handle file attachments from model data.

Methods:
- `resolve(array $attachmentPaths, array $modelData): array`
  - Extract URLs from model data using paths
  - Validate URLs are accessible
  - Download and convert to data URLs
  - Return array of attachment data

- `downloadFile(string $url): ?array`
  - Download file from URL
  - Determine MIME type
  - Convert to base64 data URL
  - Return formatted attachment or null if failed

- `validateUrl(string $url): bool`
  - Check if URL is valid
  - Check if resource exists
  - Return validation status

### 3. Queue Job

#### ProcessLLMInferenceJob
Async processing job.

Properties:
- `$interactionId` - ID of interaction to process
- `$processId` - ID of LLM process
- `$modelId` - ID of model instance
- `$modelClass` - Class name of model

Handle method:
1. Load LLMInteraction and update status to 'processing'
2. Load LLMProcess
3. Load model with dependencies
4. Flatten model data to array
5. Process templates with TemplateProcessor
6. Resolve attachments with AttachmentResolver
7. Build OpenRouter request
8. Send request via OpenRouterClient
9. Store response in LLMInteraction
10. Update status to 'completed'
11. Fire completion event

Failed method:
1. Update interaction status to 'failed'
2. Store error message
3. Fire failure event

### 4. Configuration

Config file should include:
```php
return [
    // Database
    'table_prefix' => 'llm_',
    'database_connection' => null, // null for default
    
    // OpenRouter
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => 'https://openrouter.ai/api/v1',
        'timeout' => 60,
        'retry_times' => 3,
        'retry_delay' => 1000,
    ],
    
    // Queue
    'queue' => [
        'connection' => null, // null for default
        'queue' => 'default',
    ],
    
    // Processing
    'processing' => [
        'default_temperature' => 0.7,
        'default_max_tokens' => 4096,
        'missing_variable_placeholder' => '', // What to show for missing variables
        'throw_on_missing_data' => false, // Global default
    ],
    
    // Storage
    'storage' => [
        'disk' => null, // null for default disk
        'attachment_timeout' => 30, // Timeout for downloading attachments
    ],
];
```

### 5. Service Provider

Register bindings:
- Bind services as singletons
- Register config publishing
- Register migration publishing
- Register facade

Boot operations:
- Load migrations if not published
- Register event listeners
- Register commands (if needed)

### 6. Facade

Static methods for easy access:
- `process($processId, $modelId, $async = true): LLMInteraction`
- `processSync($processId, $modelId): LLMInteraction`
- `getProcess($slug): ?LLMProcess`
- `getInteraction($id): ?LLMInteraction`

### 7. Migrations

LLM Processes table:
- All fields from LLMProcess model
- Unique index on slug
- Index on model_class
- Index on is_active

LLM Interactions table:
- All fields from LLMInteraction model
- Index on process_id
- Index on status
- Composite index on model_type and model_id

## Usage Flow

1. Create an LLMProcess via database/admin:
```
name: "Extract Property Insights"
model_class: "App\Models\Property"
dependencies: ["images", "owner.profile", "listings"]
system_prompt: "You are analyzing property {{id}} located at {{address}}"
user_prompt: "Analyse {{description}} and images at {{images.0.url}}"
```

2. Invoke the process:
```php
$interaction = LLMProcessor::process($processId, $propertyId);
```

3. System automatically:
   - Loads Property model with images, owner.profile, listings
   - Flattens all data to dot notation
   - Replaces variables in prompts
   - Sends to OpenRouter
   - Stores response

## Data Flow

1. **Input**: process_id, model_id
2. **Load Process**: Get configuration from database
3. **Load Model**: Instantiate model class and eager load relations
4. **Flatten Data**: Convert to dot-notation array
5. **Process Templates**: Replace variables in prompts
6. **Resolve Attachments**: Download files if specified
7. **Send to LLM**: Via OpenRouter
8. **Store Response**: In LLMInteraction record
9. **Return**: Interaction record for status checking

## Error Handling

- Invalid model class: Throw ModelNotFoundException
- Model ID not found: Throw ModelNotFoundException  
- Missing required data: Handle based on terminate_on_missing_data flag
- OpenRouter API errors: Retry with backoff, eventually mark as failed
- Attachment download failures: Log and continue without attachment
- Invalid process configuration: Throw LLMProcessException

## Key Design Decisions

1. **No Model Modifications**: Models don't need any changes - process configuration handles everything
2. **Flexible Relations**: Support any level of nested relations via Laravel's eager loading
3. **Storage Agnostic**: Use Laravel's Storage facade for any file operations
4. **Queue Agnostic**: Use Laravel's queue system, works with any driver
5. **Simple Invocation**: Just need process ID and model ID
6. **Graceful Failures**: System continues with warnings rather than hard failures where possible