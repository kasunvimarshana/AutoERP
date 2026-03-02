<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mutable balance cache — updated atomically with each ledger entry
 * under pessimistic locking.
 */
class StockBalanceModel extends Model
{
    protected $table = 'stock_balances';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'product_id',
        'quantity_on_hand',
        'quantity_reserved',
        'average_cost',
        'updated_at',
    ];

    protected $casts = [
        'quantity_on_hand' => 'string',
        'quantity_reserved' => 'string',
        'average_cost' => 'string',
        'updated_at' => 'datetime',
    ];
}
