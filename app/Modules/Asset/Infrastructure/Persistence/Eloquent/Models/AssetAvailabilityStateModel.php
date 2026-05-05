<?php

declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class AssetAvailabilityStateModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'asset_availability_states';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'asset_id',
        'availability_status',
        'reason_code',
        'source_type',
        'source_id',
        'effective_from',
        'effective_to',
        'updated_by',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'asset_id' => 'integer',
        'source_id' => 'integer',
        'updated_by' => 'integer',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
