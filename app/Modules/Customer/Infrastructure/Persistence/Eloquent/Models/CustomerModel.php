<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class CustomerModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'customers';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'email',
        'phone',
        'address',
        'tax_number',
        'currency',
        'credit_limit',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'float',
        'balance'      => 'float',
        'is_active'    => 'boolean',
    ];
}
