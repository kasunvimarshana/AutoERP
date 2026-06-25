<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LogicException;
use Modules\Core\Models\Concerns\HasStatusScope;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Models\Concerns\HasTenantScope;
use Modules\Tenant\Models\TenantModel;

final class UserModel extends Authenticatable
{
    use HasStatusScope;
    use HasTenantScope;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $guarded = ['id', 'is_platform_operator', 'platform_login_email'];

    protected $hidden = ['password', 'remember_token', 'is_platform_operator', 'platform_login_email'];

    protected static function booted(): void
    {
        static::updating(function (self $user): void {
            if ($user->isDirty('tenant_id')) {
                throw new LogicException('User tenant ownership cannot be changed after creation.');
            }
        });

        static::saving(function (self $user): void {
            $isPlatformOperator = (bool) $user->getAttribute('is_platform_operator');
            $tenantId = $user->getAttribute('tenant_id');
            $platformEmail = trim((string) $user->getAttribute('platform_login_email'));

            if (! app()->bound(TenantExecutionContextInterface::class)) {
                throw new LogicException('User writes require an explicit tenant or platform execution context.');
            }

            $executionContext = app(TenantExecutionContextInterface::class);
            $executionTenantId = $executionContext->tenantId();

            if ($isPlatformOperator) {
                if ($tenantId !== null || $platformEmail === '') {
                    throw new LogicException(
                        'Platform operators must be tenant-independent and have a platform login email.',
                    );
                }

                if (! $executionContext->isControlPlane() || $executionTenantId !== null) {
                    throw new LogicException('Platform operators can only be written in the platform control plane.');
                }

                return;
            }

            if (! is_numeric($tenantId) || (int) $tenantId < 1) {
                throw new LogicException('Tenant users must belong to exactly one tenant.');
            }

            if ($platformEmail !== '') {
                throw new LogicException('Tenant users cannot have a platform login email.');
            }

            if ($executionTenantId !== null && $executionTenantId !== (int) $tenantId) {
                throw new LogicException('Tenant context mismatch while writing a user.');
            }

            if ($executionTenantId === null && ! $executionContext->isControlPlane()) {
                throw new LogicException('Tenant user writes require an active tenant execution context.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'metadata' => 'array',
            'preferences' => 'array',
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'is_platform_operator' => 'boolean',
            'row_version' => 'integer',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(UserRoleModel::class, 'user_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(UserPermissionModel::class, 'user_id');
    }

    public function organizationUnitAssignments(): HasMany
    {
        return $this->hasMany(UserOrganizationUnitModel::class, 'user_id');
    }


    public function platformPermissionAssignments(): HasMany
    {
        return $this->hasMany(PlatformOperatorPermissionModel::class, 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocumentModel::class, 'user_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDeviceModel::class, 'user_id');
    }
}
