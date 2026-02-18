<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

/**
 * Payment Allocation Model
 *
 * Links payments to invoices.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $payment_id
 * @property string $invoice_id
 * @property float $amount
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PaymentAllocation extends BaseModel
{
    use HasFactory;

    protected $table = 'payment_allocations';

    protected $fillable = [
        'tenant_id',
        'payment_id',
        'invoice_id',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the payment that owns this allocation.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    /**
     * Get the invoice associated with this allocation.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
