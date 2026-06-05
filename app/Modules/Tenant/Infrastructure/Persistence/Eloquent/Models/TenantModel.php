<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class TenantModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'tenants';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'currency_id' => 'integer',
            'tenant_plan_id' => 'integer',
            'cross_org_transactions' => 'boolean',
            'is_active' => 'boolean',
            'is_isolated' => 'boolean',
            'metadata' => 'array',
            'row_version' => 'integer',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }
}
