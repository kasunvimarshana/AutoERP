<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PaymentAllocationModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'payment_allocations';

    protected $fillable = [
        'tenant_id', 'payment_id', 'invoice_type', 'invoice_id', 'allocated_amount',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:6',
    ];
}
