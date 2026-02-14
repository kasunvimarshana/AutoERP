<?php

namespace App\Modules\Billing\Models;

use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Modules\CRM\Models\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Invoice Model
 * 
 * Represents a customer invoice
 */
class Invoice extends Model
{
    use HasFactory, TenantScoped, HasUuid, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($invoice) {
            // Auto-calculate total_amount if subtotal, tax_amount, or discount_amount are set
            if ($invoice->isDirty(['subtotal', 'tax_amount', 'discount_amount']) || !$invoice->exists) {
                $invoice->total_amount = ($invoice->subtotal ?? 0) 
                                       + ($invoice->tax_amount ?? 0) 
                                       - ($invoice->discount_amount ?? 0);
            }
        });
    }

    protected static function newFactory()
    {
        return \Database\Factories\InvoiceFactory::new();
    }

    /**
     * Get the customer for this invoice
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get invoice items
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get payments for this invoice
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
