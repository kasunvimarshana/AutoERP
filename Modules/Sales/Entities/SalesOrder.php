<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;
use Modules\Sales\Database\Factories\SalesOrderFactory;

/**
 * Sales Order Entity
 *
 * Represents a sales order from a customer.
 * Central entity in the order-to-cash process.
 */
class SalesOrder extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_id',
        'order_date',
        'delivery_date',
        'status',
        'payment_status',
        'payment_method',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'notes',
        'internal_notes',
        'terms_and_conditions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    protected $translatable = ['notes', 'internal_notes', 'terms_and_conditions'];

    /**
     * Get the customer for the sales order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the sales order lines.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lines()
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    /**
     * Get the shipping address.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function shippingAddress()
    {
        return $this->morphOne('App\Models\Address', 'addressable')
            ->where('type', 'shipping');
    }

    /**
     * Get the billing address.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function billingAddress()
    {
        return $this->morphOne('App\Models\Address', 'addressable')
            ->where('type', 'billing');
    }

    /**
     * Calculate and update totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->lines()->sum('line_total');
        $taxAmount = $this->lines()->sum('tax_amount');

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount + $this->shipping_amount - $this->discount_amount,
        ]);
    }

    /**
     * Confirm the sales order.
     */
    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);

        // Fire event
        event(new \Modules\Sales\Events\SalesOrderConfirmed($this));
    }

    /**
     * Check if order is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SalesOrderFactory
    {
        return SalesOrderFactory::new();
    }
}
