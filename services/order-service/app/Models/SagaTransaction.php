<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SagaTransaction extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Step constants
    // -------------------------------------------------------------------------

    public const STEP_CREATE_ORDER         = 'CREATE_ORDER';
    public const STEP_RESERVE_INVENTORY    = 'RESERVE_INVENTORY';
    public const STEP_PROCESS_PAYMENT      = 'PROCESS_PAYMENT';
    public const STEP_SEND_NOTIFICATION    = 'SEND_NOTIFICATION';

    public const STEPS = [
        self::STEP_CREATE_ORDER,
        self::STEP_RESERVE_INVENTORY,
        self::STEP_PROCESS_PAYMENT,
        self::STEP_SEND_NOTIFICATION,
    ];

    // -------------------------------------------------------------------------
    // Status constants
    // -------------------------------------------------------------------------

    public const STATUS_PENDING      = 'pending';
    public const STATUS_COMPLETED    = 'completed';
    public const STATUS_FAILED       = 'failed';
    public const STATUS_COMPENSATING = 'compensating';
    public const STATUS_COMPENSATED  = 'compensated';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_COMPENSATING,
        self::STATUS_COMPENSATED,
    ];

    // -------------------------------------------------------------------------
    // Eloquent configuration
    // -------------------------------------------------------------------------

    protected $fillable = [
        'order_id',
        'saga_id',
        'step',
        'status',
        'payload',
        'result',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'result'       => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // -------------------------------------------------------------------------
    // Transition helpers
    // -------------------------------------------------------------------------

    /**
     * Mark this step as successfully completed.
     */
    public function markCompleted(array $result = []): bool
    {
        $this->status       = self::STATUS_COMPLETED;
        $this->result       = $result;
        $this->completed_at = now();

        return $this->save();
    }

    /**
     * Mark this step as failed.
     */
    public function markFailed(string $errorMessage = '', array $result = []): bool
    {
        $this->status        = self::STATUS_FAILED;
        $this->error_message = $errorMessage;
        $this->result        = $result;
        $this->completed_at  = now();

        return $this->save();
    }

    /**
     * Mark this step as currently being compensated (rollback in progress).
     */
    public function markCompensating(): bool
    {
        $this->status = self::STATUS_COMPENSATING;

        return $this->save();
    }

    /**
     * Mark this step as fully compensated (rollback complete).
     */
    public function markCompensated(): bool
    {
        $this->status       = self::STATUS_COMPENSATED;
        $this->completed_at = now();

        return $this->save();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCompensated(): bool
    {
        return $this->status === self::STATUS_COMPENSATED;
    }

    /**
     * Safe API representation of the saga transaction.
     */
    public function toApiArray(): array
    {
        return [
            'id'            => $this->id,
            'order_id'      => $this->order_id,
            'saga_id'       => $this->saga_id,
            'step'          => $this->step,
            'status'        => $this->status,
            'result'        => $this->result,
            'error_message' => $this->error_message,
            'started_at'    => $this->started_at,
            'completed_at'  => $this->completed_at,
        ];
    }
}
