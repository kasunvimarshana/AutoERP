<?php

namespace Modules\POS\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrderLineModel extends Model
{
    protected $table = 'pos_order_lines';

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'pos_order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'line_total',
    ];

    protected $casts = [
        'quantity'   => 'string',
        'unit_price' => 'string',
        'discount'   => 'string',
        'tax_rate'   => 'string',
        'line_total' => 'string',
    ];

    public function order()
    {
        return $this->belongsTo(PosOrderModel::class, 'pos_order_id');
    }
}
