<?php

declare(strict_types=1);

namespace Modules\Tenant\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Tenant\Constants\TenantPermission;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Models\TenantModel;
use Ramsey\Uuid\Uuid;

final class TenantSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        DB::transaction(function (): void {
            $code = strtoupper(trim((string) env('AUTOERP_TENANT_CODE', 'AUTOERP')));

            TenantModel::query()->updateOrCreate(
                ['code' => $code],
                [
                    'uuid' => Uuid::uuid5(
                        Uuid::NAMESPACE_DNS,
                        'autoerp.local/tenant/'.$code,
                    )->toString(),
                    'name' => trim((string) env('AUTOERP_TENANT_NAME', 'AutoERP')),
                    'slug' => Str::slug($code),
                    'cross_org_transactions' => false,
                    'status' => TenantStatus::DRAFT,
                    'status_reason' => 'Awaiting bootstrap prerequisites.',
                    'activated_at' => null,
                    'row_version' => 1,
                    'metadata' => ['seed_source' => 'tenant_module'],
                ],
            );

            $this->seedPermissions();
        }, 3);
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $guard = (string) config('module-auth.protected_route_guard', 'auth-api');
        $tenantIds = TenantModel::query()
            ->where('status', '!=', TenantStatus::ARCHIVED)
            ->pluck('id');

        foreach ($tenantIds as $tenantId) {
            foreach (TenantPermission::descriptions() as $name => $description) {
                $identity = [
                    'tenant_id' => (int) $tenantId,
                    'name' => $name,
                    'guard_name' => $guard,
                ];
                $existing = DB::table('permissions')
                    ->where($identity)
                    ->first(['id', 'row_version']);
                $values = [
                    'organization_unit_id' => null,
                    'module' => 'Tenant',
                    'description' => $description,
                    'deleted_at' => null,
                    'updated_at' => now(),
                ];

                if ($existing === null) {
                    DB::table('permissions')->insert([
                        ...$identity,
                        ...$values,
                        'row_version' => 1,
                        'created_at' => now(),
                    ]);
                    continue;
                }

                DB::table('permissions')->where('id', $existing->id)->update([
                    ...$values,
                    'row_version' => max(1, (int) $existing->row_version) + 1,
                ]);
            }
        }
    }
}
