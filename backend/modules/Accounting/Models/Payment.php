<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Enums\PaymentMethod;
use Modules\Accounting\Enums\PaymentStatus;
use Modules\Core\Models\BaseModel;

/**
 * Payment Model
 *
 * Represents a payment received from a customer.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $payment_number
 * @property string|null $customer_id
 * @property PaymentMethod $payment_method
 * @property PaymentStatus $status
 * @property \Illuminate\Support\Carbon $payment_date
 * @property float $amount
 * @property string $currency_code
 * @property string|null $reference
 * @property string|null $notes
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Payment extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'payment_number',
        'customer_id',
        'payment_method',
        'status',
        'payment_date',
        'amount',
        'currency_code',
        'reference',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the customer associated with this payment.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Sales\Models\Customer::class, 'customer_id');
    }

    /**
     * Get payment allocations for this payment.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'payment_id');
    }
}
