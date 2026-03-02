<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $table = 'stock_adjustments';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'reference_no',
        'reason',
        'total_amount',
        'status',
        'adjusted_by',
        'adjusted_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:4',
        'adjusted_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant.id')) {
                $query->where('stock_adjustments.tenant_id', app('tenant.id'));
            }
        });
    }

    public function warehouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
