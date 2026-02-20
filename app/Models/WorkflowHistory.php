<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'workflow_instance_id',
        'transition_id',
        'from_state_id',
        'to_state_id',
        'transitioned_by',
        'comment',
        'context',
        'transitioned_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'transitioned_at' => 'datetime',
        ];
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
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
