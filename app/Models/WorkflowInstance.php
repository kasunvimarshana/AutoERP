<?php

namespace App\Models;

use App\Enums\WorkflowInstanceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstance extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'workflow_definition_id',
        'current_state_id',
        'entity_type',
        'entity_id',
        'status',
        'initiated_by',
        'context',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkflowInstanceStatus::class,
            'context' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function currentState(): BelongsTo
    {
        return $this->belongsTo(WorkflowState::class, 'current_state_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class);
    }
}
