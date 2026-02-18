<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Product Rack Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $location_id
 * @property string $product_id
 * @property string|null $rack
 * @property string|null $row
 * @property string|null $position
 */
class ProductRack extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_product_racks';

    protected $fillable = [
        'tenant_id',
        'location_id',
        'product_id',
        'rack',
        'row',
        'position',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }
}
