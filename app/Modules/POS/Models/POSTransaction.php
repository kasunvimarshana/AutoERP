<?php

namespace App\Modules\POS\Models;

use App\Core\Traits\TenantScoped;
use App\Models\User;
use App\Modules\Branch\Models\Branch;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * POS Transaction Model
 *
 * Represents point of sale transactions
 */
class POSTransaction extends Model
{
    use SoftDeletes, TenantScoped;

    protected $table = 'pos_transactions';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'customer_id',
        'cashier_id',
        'transaction_number',
        'transaction_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'paid_amount',
        'change_amount',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the transaction
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Get the branch where transaction occurred
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the cashier (user)
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get transaction items
     */
    public function items(): HasMany
    {
        return $this->hasMany(POSTransactionItem::class, 'transaction_id');
    }
}
