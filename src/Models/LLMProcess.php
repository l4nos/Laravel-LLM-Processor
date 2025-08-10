<?php

namespace Lanos\LLMProcessor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Lanos\LLMProcessor\Exceptions\LLMProcessException;
use Lanos\LLMProcessor\Models\LLMInteraction;

class LLMProcess extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'llm_processes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'model_class',
        'dependencies',
        'system_prompt',
        'user_prompt',
        'model',
        'temperature',
        'max_output_tokens',
        'output_type',
        'structured_output_schema',
        'attachments',
        'terminate_on_missing_data',
        'use_web_search',
        'use_reasoning',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'dependencies' => 'array',
        'structured_output_schema' => 'array',
        'attachments' => 'array',
        'temperature' => 'decimal:2',
        'terminate_on_missing_data' => 'boolean',
        'use_web_search' => 'boolean',
        'use_reasoning' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'max_output_tokens' => 'integer',
    ];

    /**
     * Get the interactions for this process.
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(LLMInteraction::class, 'process_id');
    }

    /**
     * Validate the process configuration.
     *
     * @throws LLMProcessException
     */
    public function validate(): void
    {
        if (empty($this->model_class)) {
            throw new LLMProcessException('Model class is required');
        }

        if (!class_exists($this->model_class)) {
            throw new LLMProcessException("Model class {$this->model_class} does not exist");
        }

        if (empty($this->system_prompt) && empty($this->user_prompt)) {
            throw new LLMProcessException('Either system prompt or user prompt is required');
        }

        if (empty($this->model)) {
            throw new LLMProcessException('Model identifier is required');
        }
    }

    /**
     * Load model instance with dependencies.
     *
     * @param string $id
     * @return Model|null
     */
    public function getModelInstance(string $id): ?Model
    {
        $modelClass = $this->model_class;
        
        if (!class_exists($modelClass)) {
            return null;
        }

        $query = $modelClass::where('id', $id);
        
        if (!empty($this->dependencies)) {
            $query->with($this->dependencies);
        }

        return $query->first();
    }
}