<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class StockMovementModel extends CoreModel
{
    protected $table = 'stock_movements';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'source_id' => 'integer',
            'source_context' => 'array',
            'source_line_id' => 'integer',
            'quantity' => 'decimal:4',
            'base_quantity' => 'decimal:4',
            'quantity_in' => 'decimal:4',
            'quantity_out' => 'decimal:4',
            'base_quantity_in' => 'decimal:4',
            'base_quantity_out' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'balance_quantity' => 'decimal:4',
            'balance_value' => 'decimal:4',
            'performed_at' => 'datetime',
        ]);
    }
}
