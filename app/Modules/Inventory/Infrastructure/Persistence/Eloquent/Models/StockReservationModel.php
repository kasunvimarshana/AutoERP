<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\ResolvesMorphTypeClass;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

class StockReservationModel extends Model
{
    use HasAudit;
    use HasTenant;
    use ResolvesMorphTypeClass;

    protected $table = 'stock_reservations';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_id',
        'location_id',
        'quantity',
        'reserved_for_type',
        'reserved_for_id',
        'expires_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'serial_id' => 'integer',
        'location_id' => 'integer',
        'reserved_for_id' => 'integer',
        'expires_at' => 'datetime',
        'quantity' => 'decimal:6',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariantModel::class, 'variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchModel::class, 'batch_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(SerialModel::class, 'serial_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'location_id');
    }

    public function reservedFor(): MorphTo
    {
        return $this->morphTo();
    }

    public function getReservedForTypeClassAttribute(): ?string
    {
        return $this->resolveMorphTypeClass($this->reserved_for_type);
    }
}
