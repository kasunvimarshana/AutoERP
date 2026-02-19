<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Workflow\Enums\StepType;

class WorkflowStep extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workflow_id',
        'name',
        'description',
        'type',
        'sequence',
        'config',
        'action_config',
        'approval_config',
        'condition_config',
        'timeout_seconds',
        'retry_count',
        'is_required',
        'metadata',
    ];

    protected $casts = [
        'type' => StepType::class,
        'config' => 'array',
        'action_config' => 'array',
        'approval_config' => 'array',
        'condition_config' => 'array',
        'timeout_seconds' => 'integer',
        'retry_count' => 'integer',
        'is_required' => 'boolean',
        'metadata' => 'array',
        'sequence' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(WorkflowCondition::class, 'step_id');
    }

    public function instanceSteps(): HasMany
    {
        return $this->hasMany(WorkflowInstanceStep::class);
    }

    public function requiresInput(): bool
    {
        return $this->type->requiresInput();
    }

    public function allowsMultipleOutputs(): bool
    {
        return $this->type->allowsMultipleOutputs();
    }

    public function getNextSteps(): array
    {
        return $this->config['next_steps'] ?? [];
    }
}
