<?php

namespace Lanos\LLMProcessor\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Lanos\LLMProcessor\Models\LLMProcess;

class LLMInteraction extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'llm_interactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'process_id',
        'model_type',
        'model_id',
        'system_prompt',
        'user_prompt',
        'attachments',
        'options',
        'status',
        'response',
        'response_metadata',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'attachments' => 'array',
        'options' => 'array',
        'response_metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the process that owns this interaction.
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(LLMProcess::class, 'process_id');
    }

    /**
     * Retrieve the original model instance.
     *
     * @return Model|null
     */
    public function getModel(): ?Model
    {
        if (!class_exists($this->model_type)) {
            return null;
        }

        return $this->model_type::find($this->model_id);
    }
}