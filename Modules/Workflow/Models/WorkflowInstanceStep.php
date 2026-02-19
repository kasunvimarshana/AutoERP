<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Workflow\Enums\InstanceStatus;

class WorkflowInstanceStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_instance_id',
        'workflow_step_id',
        'status',
        'input_data',
        'output_data',
        'started_at',
        'completed_at',
        'failed_at',
        'error_message',
        'retry_count',
        'metadata',
    ];

    protected $casts = [
        'status' => InstanceStatus::class,
        'input_data' => 'array',
        'output_data' => 'array',
        'metadata' => 'array',
        'retry_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function start(): void
    {
        $this->update([
            'status' => InstanceStatus::RUNNING,
            'started_at' => now(),
        ]);
    }

    public function complete(array $outputData = []): void
    {
        $this->update([
            'status' => InstanceStatus::COMPLETED,
            'output_data' => $outputData,
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
}
