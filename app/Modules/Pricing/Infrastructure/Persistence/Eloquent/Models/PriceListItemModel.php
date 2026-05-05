<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PriceListItemModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'price_list_items';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'price_list_id',
        'product_id',
        'variant_id',
        'uom_id',
        'min_quantity',
        'price',
        'discount_pct',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'price_list_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'uom_id' => 'integer',
        'min_quantity' => 'decimal:6',
        'price' => 'decimal:6',
        'discount_pct' => 'decimal:6',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceListModel::class, 'price_list_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariantModel::class, 'variant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }
}
