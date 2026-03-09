<?php

declare(strict_types=1);

namespace App\Domain\Saga\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Saga Transaction Model
 *
 * Represents a distributed transaction coordinated by the Saga orchestrator.
 * Tracks the overall state and all individual steps.
 *
 * States: PENDING → RUNNING → COMPLETED | COMPENSATING → COMPENSATED | FAILED
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $saga_type        e.g., 'create_order', 'transfer_stock'
 * @property string $status           PENDING, RUNNING, COMPLETED, COMPENSATING, COMPENSATED, FAILED
 * @property array  $payload          The original input data
 * @property array|null $result       Final result data
 * @property string|null $failure_reason
 * @property int $retry_count
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 */
class SagaTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'saga_transactions';

    // Valid saga statuses
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_COMPENSATING = 'COMPENSATING';
    public const STATUS_COMPENSATED = 'COMPENSATED';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'tenant_id',
        'saga_type',
        'status',
        'payload',
        'result',
        'failure_reason',
        'retry_count',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'retry_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the steps for this saga transaction.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(SagaStep::class, 'saga_id')->orderBy('step_order');
    }

    /**
     * Get completed steps (for compensation ordering).
     */
    public function completedSteps(): HasMany
    {
        return $this->hasMany(SagaStep::class, 'saga_id')
            ->where('status', SagaStep::STATUS_COMPLETED)
            ->orderByDesc('step_order');
    }

    /**
     * Check if the saga is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_COMPENSATED,
            self::STATUS_FAILED,
        ], true);
    }
}
