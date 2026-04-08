<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class SupplierModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'suppliers';

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
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'float',
        'is_active'    => 'boolean',
    ];
}
