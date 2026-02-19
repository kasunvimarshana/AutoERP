<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Tenant\Traits\TenantScoped;
use Modules\Workflow\Enums\ApprovalStatus;

class Approval extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'workflow_instance_id',
        'workflow_step_id',
        'approver_id',
        'delegated_to',
        'status',
        'priority',
        'subject',
        'description',
        'comments',
        'decision_data',
        'requested_at',
        'responded_at',
        'escalated_at',
        'escalation_level',
        'due_at',
        'metadata',
    ];

    protected $casts = [
        'status' => ApprovalStatus::class,
        'decision_data' => 'array',
        'metadata' => 'array',
        'priority' => 'integer',
        'escalation_level' => 'integer',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'escalated_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function delegatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function isSuccess(): bool
    {
        return $this->status->isSuccess();
    }

    public function approve(array $data = []): void
    {
        $this->update([
            'status' => ApprovalStatus::APPROVED,
            'decision_data' => $data,
            'responded_at' => now(),
        ]);
    }

    public function reject(array $data = []): void
    {
        $this->update([
            'status' => ApprovalStatus::REJECTED,
            'decision_data' => $data,
            'responded_at' => now(),
        ]);
    }

    public function delegate(int $userId): void
    {
        $this->update([
            'status' => ApprovalStatus::DELEGATED,
            'delegated_to' => $userId,
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && ! $this->isFinal();
    }
}
