<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\User\Constants\UserPermission;

final class SuperAdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        DB::transaction(function (): void {
            $roles = DB::table('roles')
                ->where('name', UserPermission::SUPER_ADMIN_ROLE)
                ->whereNull('deleted_at')
                ->orderBy('tenant_id')
                ->orderBy('id')
                ->get(['id', 'tenant_id', 'guard_name']);

            foreach ($roles as $role) {
                if ($role->tenant_id === null) {
                    continue;
                }

                $permissionIds = DB::table('permissions')
                    ->where('tenant_id', $role->tenant_id)
                    ->where('guard_name', $role->guard_name)
                    ->whereNull('deleted_at')
                    ->pluck('id');

                foreach ($permissionIds as $permissionId) {
                    DB::table('role_permissions')->updateOrInsert(
                        [
                            'tenant_id' => $role->tenant_id,
                            'role_id' => $role->id,
                            'permission_id' => $permissionId,
                        ],
                        [
                            'row_version' => 1,
                            'metadata' => json_encode(['seed_source' => 'super_admin_permission_seeder'], JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ],
                    );
                }
            }
        }, 3);
    }
}
