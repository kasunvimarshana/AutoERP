<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\Sales\Enums\SalesDeliveryStatus;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class SalesDelivery extends TenantOwnedModel
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'delivery_date' => 'date',
            'status' => SalesDeliveryStatus::class,
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ]);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class);
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesDeliveryLine::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(SalesHeaderAdjustment::class, 'source_id')
            ->where('source_type', 'sales_delivery');
    }
}
