<?php

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