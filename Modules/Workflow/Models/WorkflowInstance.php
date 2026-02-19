<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Tenant\Traits\TenantScoped;
use Modules\Workflow\Enums\InstanceStatus;

class WorkflowInstance extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'workflow_id',
        'status',
        'entity_type',
        'entity_id',
        'context',
        'current_step_id',
        'started_by',
        'started_at',
        'completed_at',
        'failed_at',
        'cancelled_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'status' => InstanceStatus::class,
        'context' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function instanceSteps(): HasMany
    {
        return $this->hasMany(WorkflowInstanceStep::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo('entity');
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function complete(): void
    {
        $this->update([
            'status' => InstanceStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function fail(string $message): void
    {
        $this->update([
            'status' => InstanceStatus::FAILED,
            'failed_at' => now(),
            'error_message' => $message,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => InstanceStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);
    }
}
