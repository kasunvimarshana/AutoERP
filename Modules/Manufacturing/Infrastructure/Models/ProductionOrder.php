<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Infrastructure\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Manufacturing\Domain\Enums\ProductionStatus;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_orders';

    protected $fillable = [
        'tenant_id',
        'reference_no',
        'product_id',
        'variant_id',
        'warehouse_id',
        'bom_id',
        'planned_quantity',
        'produced_quantity',
        'total_cost',
        'wastage_percent',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'planned_quantity'  => 'decimal:4',
        'produced_quantity' => 'decimal:4',
        'total_cost'        => 'decimal:4',
        'wastage_percent'   => 'decimal:2',
        'status'            => ProductionStatus::class,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $q): void {
            if (app()->bound('tenant.id')) {
                $q->where('production_orders.tenant_id', app('tenant.id'));
            }
        });
    }

    public function bom(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }
}
