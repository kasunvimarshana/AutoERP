<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ComboItemModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'combo_items';

    protected $fillable = [
        'tenant_id',
        'combo_product_id',
        'component_product_id',
        'component_variant_id',
        'quantity',
        'uom_id',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'combo_product_id' => 'integer',
        'component_product_id' => 'integer',
        'component_variant_id' => 'integer',
        'uom_id' => 'integer',
        'quantity' => 'decimal:6',
        'metadata' => 'array',
    ];

    public function comboProduct(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'combo_product_id');
    }

    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'component_product_id');
    }

    public function componentVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariantModel::class, 'component_variant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }
}
