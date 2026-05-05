<?php

declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class AssetModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'assets';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'asset_code',
        'name',
        'asset_kind',
        'usage_profile',
        'ownership_type',
        'owner_supplier_id',
        'registration_number',
        'vin',
        'manufacturer',
        'model',
        'model_year',
        'color',
        'current_meter_reading',
        'meter_unit',
        'status',
        'commissioned_on',
        'retired_on',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'owner_supplier_id' => 'integer',
        'model_year' => 'integer',
        'current_meter_reading' => 'decimal:6',
        'commissioned_on' => 'date',
        'retired_on' => 'date',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
