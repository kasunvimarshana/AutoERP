<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'address',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant.id')) {
                $query->where('warehouses.tenant_id', app('tenant.id'));
            }
        });
    }

    public function stockEntries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockLedgerEntry::class, 'warehouse_id');
    }
}
