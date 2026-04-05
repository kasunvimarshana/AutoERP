<?php
declare(strict_types=1);
namespace Modules\POS\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransactionLineModel extends Model
{
    public const UPDATED_AT = null;
    protected $table = 'pos_transaction_lines';
    protected $fillable = [
        'pos_transaction_id', 'product_id', 'variant_id', 'product_name', 'sku',
        'quantity', 'unit_price', 'discount_amount', 'tax_amount', 'line_total',
    ];
    protected $casts = [
        'id'                  => 'int',
        'pos_transaction_id'  => 'int',
        'product_id'          => 'int',
        'variant_id'          => 'int',
        'quantity'            => 'float',
        'unit_price'          => 'float',
        'discount_amount'     => 'float',
        'tax_amount'          => 'float',
        'line_total'          => 'float',
        'created_at'          => 'datetime',
    ];
}
