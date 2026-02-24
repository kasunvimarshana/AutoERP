<?php

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class InventoryValuationEntryModel extends Model
{
    use HasUuids, HasTenantScope;

    protected $table = 'inventory_valuation_entries';

    protected $fillable = [
        'id',
        'tenant_id',
        'product_id',
        'movement_type',
        'qty',
        'unit_cost',
        'total_value',
        'running_balance_qty',
        'running_balance_value',
        'valuation_method',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'qty'                   => 'string',
        'unit_cost'             => 'string',
        'total_value'           => 'string',
        'running_balance_qty'   => 'string',
        'running_balance_value' => 'string',
    ];
}
