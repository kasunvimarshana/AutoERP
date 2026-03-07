<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saga audit log entry.
 *
 * Each row represents a state transition of a single step
 * within a distributed Saga transaction.  This log is critical
 * for observability and for recovery after a service crash.
 *
 * @property string      $id
 * @property string      $saga_id     Correlation ID linking all steps of one transaction.
 * @property string|null $order_id
 * @property string      $step_name
 * @property string      $status      completed|failed|compensated|compensation_failed
 * @property string|null $error_message
 */
class SagaLog extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null; // Saga logs are append-only

    /** @var array<string> */
    protected $fillable = [
        'saga_id',
        'order_id',
        'step_name',
        'status',
        'error_message',
    ];

    /**
     * @return BelongsTo<Order, SagaLog>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
