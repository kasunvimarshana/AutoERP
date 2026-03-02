<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * WorkflowInstance entity.
 *
 * Represents a running instance of a workflow attached to a specific entity.
 */
class WorkflowInstance extends Model
{
    use HasTenant;

    protected $table = 'workflow_instances';

    protected $fillable = [
        'tenant_id',
        'workflow_definition_id',
        'entity_type',
        'entity_id',
        'current_state_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'entity_id'    => 'integer',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function currentState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class, 'current_state_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowTransitionLog::class);
    }
}
