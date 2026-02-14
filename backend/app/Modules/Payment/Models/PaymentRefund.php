<?php

namespace App\Modules\Payment\Models;

use App\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="PaymentRefund",
 *     type="object",
 *     title="PaymentRefund",
 *     description="Payment refund model",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="payment_id", type="string", format="uuid"),
 *     @OA\Property(property="refund_number", type="string", example="REF-2026-001"),
 *     @OA\Property(property="refund_date", type="string", format="date"),
 *     @OA\Property(property="amount", type="number", format="float", example=500.00),
 *     @OA\Property(property="reason", type="string"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "failed"}),
 *     @OA\Property(property="processed_by", type="string", format="uuid", nullable=true),
 *     @OA\Property(property="notes", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class PaymentRefund extends BaseModel
{
    protected $fillable = [
        'payment_id',
        'refund_number',
        'refund_date',
        'amount',
        'reason',
        'status',
        'processed_by',
        'notes',
    ];

    protected $casts = [
        'refund_date' => 'date',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canBeProcessed(): bool
    {
        return $this->status === 'pending';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPayment($query, string $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeByDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('refund_date', [$from, $to]);
    }
}
