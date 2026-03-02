<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class StorefrontOrderModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'storefront_orders';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'reference',
        'status',
        'currency',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'billing_name',
        'billing_email',
        'billing_phone',
        'shipping_address',
        'notes',
        'cart_token',
    ];
}
