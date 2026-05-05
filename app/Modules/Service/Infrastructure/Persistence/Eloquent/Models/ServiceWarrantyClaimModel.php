<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ServiceWarrantyClaimModel extends Model
{
    use HasAudit;
    use HasTenant;
    use SoftDeletes;

    protected $table = 'service_warranty_claims';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'service_work_order_id',
        'supplier_id',
        'warranty_provider',
        'claim_number',
        'status',
        'currency_id',
        'claim_amount',
        'approved_amount',
        'received_amount',
        'submitted_at',
        'resolved_at',
        'journal_entry_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'org_unit_id' => 'integer',
        'row_version' => 'integer',
        'service_work_order_id' => 'integer',
        'supplier_id' => 'integer',
        'currency_id' => 'integer',
        'claim_amount' => 'decimal:6',
        'approved_amount' => 'decimal:6',
        'received_amount' => 'decimal:6',
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
        'journal_entry_id' => 'integer',
        'metadata' => 'array',
    ];
}
