<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable ledger entry — no updates or deletes.
 * Uses only created_at; no updated_at column.
 */
class StockLedgerEntryModel extends Model
{
    protected $table = 'stock_ledger_entries';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'product_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'quantity' => 'string',
        'unit_cost' => 'string',
        'total_cost' => 'string',
        'created_at' => 'datetime',
    ];
}
