<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\ResolvesMorphTypeClass;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int|null $batch_id
 * @property int $location_id
 * @property string $valuation_method
 * @property string $layer_date
 * @property string $quantity_in
 * @property string $quantity_remaining
 * @property string $unit_cost
 * @property string $total_cost
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property bool $is_closed
 */
class InventoryCostLayerModel extends Model
{
    use HasAudit;
    use HasTenant;
    use ResolvesMorphTypeClass;

    protected $table = 'inventory_cost_layers';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'product_id',
        'variant_id',
        'batch_id',
        'location_id',
        'valuation_method',
        'layer_date',
        'quantity_in',
        'quantity_remaining',
        'unit_cost',
        'reference_type',
        'reference_id',
        'is_closed',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'location_id' => 'integer',
        'reference_id' => 'integer',
        'valuation_method' => 'string',
        'layer_date' => 'date',
        'is_closed' => 'boolean',
        'quantity_in' => 'decimal:6',
        'quantity_remaining' => 'decimal:6',
        'unit_cost' => 'decimal:6',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariantModel::class, 'variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchModel::class, 'batch_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'location_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getReferenceTypeClassAttribute(): ?string
    {
        return $this->resolveMorphTypeClass($this->reference_type);
    }
}
