<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class UserTenant extends Model
{
    use HasTenantAndOrganizationScopes;

    protected $table = 'user_tenants';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'is_default' => 'boolean',
        ];
    }

    #[Scope]
    protected function defaults(Builder $query): void
    {
        $query->where('is_default', true);
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User',
            'user_id'
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\Role',
            'role_id'
        );
    }
}
