<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ServiceWorkOrderModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'service_work_orders';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'job_card_number',
        'asset_id',
        'customer_id',
        'opened_by',
        'assigned_team_org_unit_id',
        'service_type',
        'priority',
        'status',
        'opened_at',
        'scheduled_start_at',
        'scheduled_end_at',
        'started_at',
        'completed_at',
        'meter_in',
        'meter_out',
        'meter_unit',
        'symptoms',
        'diagnosis',
        'resolution',
        'billing_mode',
        'currency_id',
        'labor_subtotal',
        'parts_subtotal',
        'other_subtotal',
        'tax_total',
        'grand_total',
        'journal_entry_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'asset_id' => 'integer',
        'customer_id' => 'integer',
        'opened_by' => 'integer',
        'assigned_team_org_unit_id' => 'integer',
        'currency_id' => 'integer',
        'journal_entry_id' => 'integer',
        'meter_in' => 'decimal:6',
        'meter_out' => 'decimal:6',
        'labor_subtotal' => 'decimal:6',
        'parts_subtotal' => 'decimal:6',
        'other_subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'opened_at' => 'datetime',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
