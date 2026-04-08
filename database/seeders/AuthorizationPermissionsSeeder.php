<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\PermissionModel;

class AuthorizationPermissionsSeeder extends Seeder
{
    /**
     * Canonical permission names expected by controller authorization.
     *
     * @var string[]
     */
    private const PERMISSIONS = [
        'tenants.view',
        'tenants.create',
        'tenants.update',
        'tenants.delete',
        'tenants.update_config',
        'tenants.view_attachments',
        'tenants.upload_attachment',
        'tenants.delete_attachment',
        'tenant_attachments.view',
        'organization_units.view',
        'organization_units.create',
        'organization_units.update',
        'organization_units.delete',
        'organization_units.move',
        'organization_units.view_attachments',
        'organization_units.upload_attachment',
        'organization_units.delete_attachment',
        'organization_unit_attachments.view',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'users.assign_role',
        'users.update_preferences',
        'users.view_attachments',
        'users.upload_attachment',
        'users.delete_attachment',
        'user_attachments.view',
        'roles.view',
        'roles.create',
        'roles.delete',
        'roles.sync_permissions',
        'permissions.view',
        'permissions.create',
        'permissions.delete',
    ];

    public function run(): void
    {
        $tenantIds = TenantModel::query()->pluck('id');

        if ($tenantIds->isEmpty()) {
            $this->command?->warn('AuthorizationPermissionsSeeder skipped: no tenants found.');

            return;
        }

        foreach ($tenantIds as $tenantId) {
            $rows = array_map(
                static fn (string $permission): array => [
                    'tenant_id' => (int) $tenantId,
                    'name' => $permission,
                    'guard_name' => 'api',
                ],
                self::PERMISSIONS,
            );

            PermissionModel::query()->upsert(
                $rows,
                ['tenant_id', 'name'],
                ['guard_name'],
            );
        }

        $this->command?->info('Authorization permissions seeded for '.count($tenantIds).' tenant(s).');
    }
}
