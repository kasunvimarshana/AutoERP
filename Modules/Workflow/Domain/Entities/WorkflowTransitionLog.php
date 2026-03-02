<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * WorkflowTransitionLog entity.
 *
 * Immutable audit record of every state transition on a workflow instance.
 */
class WorkflowTransitionLog extends Model
{
    use HasTenant;

    protected $table = 'workflow_transition_logs';

    protected $fillable = [
        'tenant_id',
        'workflow_instance_id',
        'from_state_id',
        'to_state_id',
        'event_name',
        'triggered_by',
        'transitioned_at',
        'notes',
    ];

    protected $casts = [
        'transitioned_at' => 'datetime',
        'triggered_by'    => 'integer',
    ];

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }
}
