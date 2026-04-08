<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class TenantModel extends BaseModel
{
    use HasUuid;

    protected $table = 'tenants';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'status',
        'plan',
        'domain',
        'logo_path',
        'settings',
        'trial_ends_at',
        'subscription_ends_at',
        'metadata',
    ];

    protected $casts = [
        'settings'              => 'array',
        'metadata'              => 'array',
        'trial_ends_at'         => 'datetime',
        'subscription_ends_at'  => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'deleted_at'            => 'datetime',
    ];

    public function orgUnits(): HasMany
    {
        return $this->hasMany(OrgUnitModel::class, 'tenant_id');
    }
}
