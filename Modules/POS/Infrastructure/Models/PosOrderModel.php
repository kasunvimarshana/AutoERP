<?php

namespace Modules\POS\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class PosOrderModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'pos_orders';

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'session_id',
        'number',
        'customer_id',
        'status',
        'payment_method',
        'subtotal',
        'tax_total',
        'total',
        'cash_tendered',
        'change_amount',
        'currency',
        'discount_code_id',
        'discount_amount',
        'created_by',
    ];

    protected $casts = [
        'subtotal'        => 'string',
        'tax_total'       => 'string',
        'total'           => 'string',
        'cash_tendered'   => 'string',
        'change_amount'   => 'string',
        'discount_amount' => 'string',
    ];

    public function lines()
    {
        return $this->hasMany(PosOrderLineModel::class, 'pos_order_id');
    }
}
