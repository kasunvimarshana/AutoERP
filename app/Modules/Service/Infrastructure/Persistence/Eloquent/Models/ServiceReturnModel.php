<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ServiceReturnModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'service_returns';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'service_work_order_id',
        'return_number',
        'return_type',
        'status',
        'reason_code',
        'processed_by',
        'processed_at',
        'currency_id',
        'total_amount',
        'journal_entry_id',
        'payment_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'service_work_order_id' => 'integer',
        'processed_by' => 'integer',
        'processed_at' => 'datetime',
        'currency_id' => 'integer',
        'total_amount' => 'decimal:6',
        'journal_entry_id' => 'integer',
        'payment_id' => 'integer',
        'metadata' => 'array',
    ];
}
