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

class SerialModel extends Model
{
    use HasAudit;
    use HasTenant;
    use ResolvesMorphTypeClass;

    protected $table = 'serials';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'product_id',
        'variant_id',
        'serial_number',
        'batch_id',
        'status',
        'current_location_id',
        'current_owner_type',
        'current_owner_id',
        'warranty_expiry',
        'notes',
        'manufacture_date',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'batch_id' => 'integer',
        'current_location_id' => 'integer',
        'current_owner_id' => 'integer',
        'status' => 'string',
        'warranty_expiry' => 'date',
        'manufacture_date' => 'date',
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

    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'current_location_id');
    }

    public function currentOwner(): MorphTo
    {
        return $this->morphTo();
    }

    public function getCurrentOwnerTypeClassAttribute(): ?string
    {
        return $this->resolveMorphTypeClass($this->current_owner_type);
    }
}
