<?php

declare(strict_types=1);

namespace Modules\Configuration\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Constants\ConfigurationPermission;

final class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('tenants')) {
            return;
        }

        $guard = (string) config('module-auth.protected_route_guard', 'auth-api');

        DB::transaction(function () use ($guard): void {
            $tenantIds = DB::table('tenants')->where('status', '!=', 'archived')->pluck('id');

            foreach ($tenantIds as $tenantId) {
                foreach (ConfigurationPermission::descriptions() as $name => $description) {
                    $identity = [
                        'tenant_id' => (int) $tenantId,
                        'name' => $name,
                        'guard_name' => $guard,
                    ];
                    $existing = DB::table('permissions')->where($identity)->first([
                        'id', 'organization_unit_id', 'module', 'description', 'row_version', 'deleted_at',
                    ]);
                    $values = [
                        'organization_unit_id' => null,
                        'module' => 'Configuration',
                        'description' => $description,
                        'deleted_at' => null,
                    ];

                    if ($existing === null) {
                        DB::table('permissions')->insert([
                            ...$identity,
                            ...$values,
                            'row_version' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        continue;
                    }

                    if (
                        $existing->organization_unit_id !== null
                        || $existing->module !== 'Configuration'
                        || $existing->description !== $description
                        || $existing->deleted_at !== null
                    ) {
                        DB::table('permissions')->where('id', $existing->id)->update([
                            ...$values,
                            'row_version' => max(1, (int) $existing->row_version) + 1,
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }, 3);
    }
}
