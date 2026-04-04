<?php
namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class TenantModel extends BaseModel
{
    protected $table = 'tenants';

    protected $casts = [
        'database_config' => 'array',
        'feature_flags'   => 'array',
        'trial_ends_at'   => 'datetime',
    ];
}
