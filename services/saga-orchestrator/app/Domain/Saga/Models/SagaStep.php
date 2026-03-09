<?php

declare(strict_types=1);

namespace App\Domain\Saga\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saga Step Model
 *
 * Represents a single step within a distributed Saga transaction.
 * Each step has a forward action and a compensation (rollback) action.
 *
 * @property string $id
 * @property string $saga_id
 * @property int $step_order
 * @property string $step_name        e.g., 'create_order', 'reserve_inventory'
 * @property string $service          e.g., 'order-service', 'inventory-service'
 * @property string $status           PENDING, RUNNING, COMPLETED, COMPENSATING, COMPENSATED, FAILED, SKIPPED
 * @property array  $request_payload  Data sent to the service
 * @property array|null $response_payload Data received from the service
 * @property string|null $failure_reason
 * @property int $retry_count
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $compensated_at
 */
class SagaStep extends Model
{
    use HasUuids;

    protected $table = 'saga_steps';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_COMPENSATING = 'COMPENSATING';
    public const STATUS_COMPENSATED = 'COMPENSATED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_SKIPPED = 'SKIPPED';

    protected $fillable = [
        'saga_id',
        'step_order',
        'step_name',
        'service',
        'endpoint',
        'compensation_endpoint',
        'status',
        'request_payload',
        'response_payload',
        'failure_reason',
        'retry_count',
        'started_at',
        'completed_at',
        'compensated_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'retry_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'compensated_at' => 'datetime',
    ];

    /**
     * Get the parent saga transaction.
     */
    public function saga(): BelongsTo
    {
        return $this->belongsTo(SagaTransaction::class, 'saga_id');
    }
}
