<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PaymentTermModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'payment_terms';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'name',
        'days',
        'is_default',
        'is_active',
        'description',
        'discount_days',
        'discount_rate',
    ];

    protected $casts = [
        'days' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'discount_days' => 'integer',
        'discount_rate' => 'decimal:6',
    ];
}
