<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PaymentMethodModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'payment_methods';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'account_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
