<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * WorkflowTransition entity.
 *
 * Represents a transition between two workflow states, triggered by a named event.
 */
class WorkflowTransition extends Model
{
    use HasTenant;

    protected $table = 'workflow_transitions';

    protected $fillable = [
        'tenant_id',
        'workflow_definition_id',
        'from_state_id',
        'to_state_id',
        'event_name',
        'guard_class',
        'action_class',
        'requires_approval',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
    ];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function fromState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class, 'from_state_id');
    }

    public function toState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class, 'to_state_id');
    }
}
