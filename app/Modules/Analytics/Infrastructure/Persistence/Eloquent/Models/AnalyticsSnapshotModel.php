<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class AnalyticsSnapshotModel extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'fleet_analytics_snapshots';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'summary_date',
        'total_rentals',
        'completed_rentals',
        'active_rentals',
        'total_revenue',
        'total_service_cost',
        'net_revenue',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'total_revenue' => 'string',
        'total_service_cost' => 'string',
        'net_revenue' => 'string',
        'summary_date' => 'date:Y-m-d',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
