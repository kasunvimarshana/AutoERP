<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class RolePermission extends Model
{
    use HasTenantAndOrganizationScopes;

    public $timestamps = false;

    protected $table = 'role_permissions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant',
            'tenant_id'
        );
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit',
            'organization_unit_id'
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\Role',
            'role_id'
        );
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\Permission',
            'permission_id'
        );
    }
}
