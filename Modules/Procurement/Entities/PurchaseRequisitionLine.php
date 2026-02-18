<?php

declare(strict_types=1);

namespace Modules\Procurement\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\Auditable;
use Modules\Core\Traits\Tenantable;
use Modules\Procurement\Database\Factories\PurchaseRequisitionLineFactory;

/**
 * Purchase Requisition Line Entity
 *
 * Represents an individual line item in a purchase requisition.
 */
class PurchaseRequisitionLine extends Model
{
    use Auditable, HasFactory, SoftDeletes, Tenantable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'purchase_requisition_id',
        'product_id',
        'description',
        'quantity',
        'unit_of_measure',
        'estimated_unit_price',
        'estimated_total',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the purchase requisition.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function purchaseRequisition()
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    /**
     * Get the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo('Modules\Inventory\Entities\Product', 'product_id');
    }

    /**
     * Calculate line total.
     */
    public function calculateTotal(): void
    {
        $this->update([
            'estimated_total' => $this->quantity * $this->estimated_unit_price,
        ]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PurchaseRequisitionLineFactory
    {
        return PurchaseRequisitionLineFactory::new();
    }
}
