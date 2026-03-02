<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class SalesOrderModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'sales_orders';

    protected $fillable = [
        'tenant_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'order_date',
        'due_date',
        'notes',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
    ];

    protected $casts = [
        'subtotal' => 'string',
        'tax_amount' => 'string',
        'discount_amount' => 'string',
        'total_amount' => 'string',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLineModel::class, 'sales_order_id');
    }
}
