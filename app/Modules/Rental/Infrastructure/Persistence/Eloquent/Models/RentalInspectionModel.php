<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class RentalInspectionModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'rental_inspections';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'rental_booking_id',
        'asset_id',
        'inspection_type',
        'inspection_status',
        'inspected_by',
        'inspected_at',
        'meter_reading',
        'fuel_level_percent',
        'damage_notes',
        'media',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'rental_booking_id' => 'integer',
        'asset_id' => 'integer',
        'inspected_by' => 'integer',
        'inspected_at' => 'datetime',
        'meter_reading' => 'decimal:6',
        'fuel_level_percent' => 'decimal:6',
        'media' => 'array',
        'metadata' => 'array',
    ];
}
