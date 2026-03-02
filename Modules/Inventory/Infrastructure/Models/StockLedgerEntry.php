<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Domain\Enums\LedgerEntryType;

class StockLedgerEntry extends Model
{
    // Ledger entries are immutable — no soft deletes
    public const UPDATED_AT = null;

    protected $table = 'stock_ledger_entries';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'warehouse_id',
        'type',
        'quantity',
        'unit_cost',
        'running_balance',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity'        => 'decimal:4',
        'unit_cost'       => 'decimal:4',
        'running_balance' => 'decimal:4',
        'type'            => LedgerEntryType::class,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant.id')) {
                $query->where('stock_ledger_entries.tenant_id', app('tenant.id'));
            }
        });
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\Product\Infrastructure\Models\Product::class, 'product_id');
    }

    public function warehouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
