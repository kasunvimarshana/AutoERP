<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Customer Group Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property float $discount_percentage
 * @property string $price_calculation_type
 * @property string|null $selling_price_group_id
 */
class CustomerGroup extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_customer_groups';

    protected $fillable = [
        'tenant_id',
        'name',
        'discount_percentage',
        'price_calculation_type',
        'selling_price_group_id',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
    ];

    public function sellingPriceGroup(): BelongsTo
    {
        return $this->belongsTo(SellingPriceGroup::class, 'selling_price_group_id');
    }
}
