<?php

declare(strict_types=1);

namespace Modules\Procurement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class PurchaseOrderModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'order_number',
        'status',
        'order_date',
        'expected_delivery_date',
        'notes',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLineModel::class, 'purchase_order_id');
    }
}
