<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ServiceJobCardModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'service_job_cards';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'job_number',
        'asset_id',
        'customer_id',
        'maintenance_plan_id',
        'service_type',
        'priority',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'odometer_in',
        'odometer_out',
        'is_billable',
        'parts_subtotal',
        'labour_subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'assigned_to',
        'ar_transaction_id',
        'journal_entry_id',
        'diagnosis',
        'work_performed',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'asset_id' => 'integer',
        'customer_id' => 'integer',
        'maintenance_plan_id' => 'integer',
        'assigned_to' => 'integer',
        'ar_transaction_id' => 'integer',
        'journal_entry_id' => 'integer',
        'is_billable' => 'boolean',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'odometer_in' => 'decimal:6',
        'odometer_out' => 'decimal:6',
        'parts_subtotal' => 'decimal:6',
        'labour_subtotal' => 'decimal:6',
        'discount_amount' => 'decimal:6',
        'tax_amount' => 'decimal:6',
        'total_amount' => 'decimal:6',
        'metadata' => 'array',
    ];
}
