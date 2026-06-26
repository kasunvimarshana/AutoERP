<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LogicException;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Models\Concerns\HasTenantScope;

final class UserModel extends Authenticatable
{
    use HasTenantScope;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'tenant_id', 'row_version', 'first_name', 'last_name', 'username', 'email',
        'email_verified_at', 'status',
        'phone', 'credentials_ready_at', 'invited_at', 'activated_at',
        'deactivated_at', 'suspended_at', 'created_by_user_id', 'updated_by_user_id',
        'deleted_by_user_id',
    ];


    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->assertTenantContext();
            $user->normalizeIdentityFields();
        });

        static::updating(function (self $user): void {
            if ($user->isDirty('tenant_id')) {
                throw new LogicException('User tenant ownership cannot be changed after creation.');
            }
            $user->assertTenantContext();
            $user->normalizeIdentityFields();
        });
    }

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'email_verified_at' => 'datetime',
            'credentials_ready_at' => 'datetime',
            'invited_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
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

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocumentModel::class, 'user_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDeviceModel::class, 'user_id');
    }

    private function assertTenantContext(): void
    {
        $tenantId = $this->getAttribute('tenant_id');
        if (! is_numeric($tenantId) || (int) $tenantId < 1) {
            throw new LogicException('A user must belong to exactly one tenant.');
        }
        if (! app()->bound(TenantExecutionContextInterface::class)) {
            throw new LogicException('User writes require an explicit tenant execution context.');
        }
        $context = app(TenantExecutionContextInterface::class);
        $executionTenantId = $context->tenantId();
        if ($executionTenantId !== null && $executionTenantId !== (int) $tenantId) {
            throw new LogicException('Tenant context mismatch while writing a user.');
        }
        if ($executionTenantId === null && ! $context->isControlPlane()) {
            throw new LogicException('User writes require an active tenant or control-plane context.');
        }
    }

    private function normalizeIdentityFields(): void
    {
        $email = strtolower(trim((string) $this->getAttribute('email')));
        $username = strtolower(trim((string) $this->getAttribute('username')));
        $this->setAttribute('email', $email);
        $this->setAttribute('username', $username === '' ? null : $username);
    }
}
