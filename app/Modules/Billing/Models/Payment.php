<?php

namespace App\Modules\Billing\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment Model
 * 
 * Represents a payment against an invoice
 */
class Payment extends Model
{
    use HasFactory, TenantScoped, HasUuid;

    protected $fillable = [
        'payment_number',
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'gateway_transaction_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the tenant that owns this payment
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the invoice for this payment
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
