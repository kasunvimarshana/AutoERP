<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class StorefrontCartModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'storefront_carts';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'token',
        'status',
        'currency',
        'subtotal',
        'tax_amount',
        'total_amount',
    ];
}
