<?php

declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class AssetAvailabilityEventModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'asset_availability_events';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'asset_id',
        'from_status',
        'to_status',
        'reason_code',
        'source_type',
        'source_id',
        'changed_by',
        'changed_at',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'asset_id' => 'integer',
        'source_id' => 'integer',
        'changed_by' => 'integer',
        'changed_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
