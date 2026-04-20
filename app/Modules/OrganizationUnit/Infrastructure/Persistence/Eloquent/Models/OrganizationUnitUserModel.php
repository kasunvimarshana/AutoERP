<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class OrganizationUnitUserModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'org_unit_users';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'user_id',
        'role',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'org_unit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            (string) config('auth.providers.users.model'),
            'user_id'
        );
    }
}
