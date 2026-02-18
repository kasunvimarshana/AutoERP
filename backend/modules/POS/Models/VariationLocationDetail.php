<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Variation Location Detail Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $variation_id
 * @property string $location_id
 * @property float $qty_available
 * @property string|null $selling_price_group_id
 * @property float|null $group_price
 */
class VariationLocationDetail extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_variation_location_details';

    protected $fillable = [
        'tenant_id',
        'variation_id',
        'location_id',
        'qty_available',
        'selling_price_group_id',
        'group_price',
    ];

    protected $casts = [
        'qty_available' => 'decimal:2',
        'group_price' => 'decimal:2',
    ];

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function sellingPriceGroup(): BelongsTo
    {
        return $this->belongsTo(SellingPriceGroup::class, 'selling_price_group_id');
    }
}
