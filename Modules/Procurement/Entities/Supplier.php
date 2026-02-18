<?php

declare(strict_types=1);

namespace Modules\Procurement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Core\Traits\Translatable;
use Modules\Procurement\Database\Factories\SupplierFactory;

/**
 * Supplier Entity
 *
 * Represents a supplier from whom goods or services are purchased.
 */
class Supplier extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'email',
        'phone',
        'mobile',
        'website',
        'tax_id',
        'payment_terms',
        'credit_limit',
        'currency',
        'rating',
        'status',
        'notes',
        'internal_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'credit_limit' => 'decimal:2',
        'rating' => 'decimal:1',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    protected $translatable = ['notes', 'internal_notes'];

    /**
     * Get the purchase requisitions for this supplier.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchaseRequisitions()
    {
        return $this->hasMany(PurchaseRequisition::class);
    }

    /**
     * Get the purchase orders for this supplier.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Check if supplier is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Update supplier rating.
     */
    public function updateRating(float $rating): void
    {
        $this->update(['rating' => max(0, min(5, $rating))]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }
}
