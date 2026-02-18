<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;
use Modules\POS\Enums\TransactionStatus;
use Modules\POS\Enums\TransactionType;
use Modules\POS\Enums\PaymentStatus;

/**
 * Transaction Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $location_id
 * @property TransactionType $type
 * @property TransactionStatus $status
 * @property string $transaction_number
 * @property string|null $contact_id
 * @property string|null $cash_register_id
 * @property \Carbon\Carbon $transaction_date
 * @property string|null $invoice_scheme_id
 * @property string|null $invoice_number
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $discount_amount
 * @property string|null $discount_type
 * @property float $shipping_charges
 * @property float $total_amount
 * @property PaymentStatus $payment_status
 * @property float $paid_amount
 * @property string|null $notes
 * @property array|null $additional_data
 * @property string $created_by
 * @property string|null $updated_by
 */
class Transaction extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_transactions';

    protected $fillable = [
        'tenant_id',
        'location_id',
        'type',
        'status',
        'transaction_number',
        'contact_id',
        'cash_register_id',
        'transaction_date',
        'invoice_scheme_id',
        'invoice_number',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'discount_type',
        'shipping_charges',
        'total_amount',
        'payment_status',
        'paid_amount',
        'notes',
        'additional_data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'payment_status' => PaymentStatus::class,
        'transaction_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_charges' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'additional_data' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TransactionLine::class, 'transaction_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class, 'transaction_id');
    }

    public function scopeByType($query, TransactionType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, TransactionStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', TransactionStatus::COMPLETED);
    }
}
