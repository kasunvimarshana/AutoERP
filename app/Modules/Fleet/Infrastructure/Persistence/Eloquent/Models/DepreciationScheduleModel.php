<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class DepreciationScheduleModel extends Model
{
    use HasTenant;

    protected $table = 'fleet_depreciation_schedules';

    protected $fillable = [
        'tenant_id', 'vehicle_id', 'method', 'useful_life_months',
        'salvage_value', 'depreciable_amount', 'monthly_depreciation_amount',
        'accumulated_depreciation', 'start_date', 'end_date', 'is_active',
    ];

    protected $casts = [
        'salvage_value'                => 'string',
        'depreciable_amount'           => 'string',
        'monthly_depreciation_amount'  => 'string',
        'accumulated_depreciation'     => 'string',
        'is_active'                    => 'boolean',
    ];
}
