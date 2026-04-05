<?php
declare(strict_types=1);
namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnLineModel extends Model
{
    protected $table = 'return_lines';
    public $timestamps = false;

    protected $fillable = [
        'return_request_id', 'product_id', 'quantity_returned', 'unit_price',
        'batch_number', 'lot_number', 'serial_number', 'reason', 'condition',
        'quality_status', 'restocked_to_warehouse_id', 'restocked_quantity',
    ];

    protected $casts = [
        'id'                        => 'int',
        'return_request_id'         => 'int',
        'product_id'                => 'int',
        'quantity_returned'         => 'float',
        'unit_price'                => 'float',
        'restocked_to_warehouse_id' => 'int',
        'restocked_quantity'        => 'float',
    ];
}
