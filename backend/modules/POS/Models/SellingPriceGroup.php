<?php

declare(strict_types=1);

namespace Modules\POS\Models;

use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Selling Price Group Model
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string|null $description
 */
class SellingPriceGroup extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'pos_selling_price_groups';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];
}
