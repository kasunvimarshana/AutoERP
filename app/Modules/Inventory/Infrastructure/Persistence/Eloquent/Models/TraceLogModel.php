<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\ResolvesMorphTypeClass;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductIdentifierModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

class TraceLogModel extends Model
{
    use HasAudit;
    use HasTenant;
    use ResolvesMorphTypeClass;

    public $timestamps = false;

    protected $table = 'trace_logs';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'entity_type',
        'entity_id',
        'identifier_id',
        'action_type',
        'reference_type',
        'reference_id',
        'source_location_id',
        'destination_location_id',
        'quantity',
        'performed_by',
        'performed_at',
        'device_id',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'entity_id' => 'integer',
        'identifier_id' => 'integer',
        'reference_id' => 'integer',
        'source_location_id' => 'integer',
        'destination_location_id' => 'integer',
        'performed_by' => 'integer',
        'action_type' => 'string',
        'quantity' => 'decimal:6',
        'performed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getEntityTypeClassAttribute(): ?string
    {
        return $this->resolveMorphTypeClass($this->entity_type);
    }

    public function getReferenceTypeClassAttribute(): ?string
    {
        return $this->resolveMorphTypeClass($this->reference_type);
    }

    public function identifier(): BelongsTo
    {
        return $this->belongsTo(ProductIdentifierModel::class, 'identifier_id');
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'source_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'destination_location_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'performed_by');
    }
}
