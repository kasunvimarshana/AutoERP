<?php

declare(strict_types=1);

namespace Modules\Audit\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Audit\Constants\AuditPermission;

final class AuditSeeder extends Seeder
{
    public function run(): void
    {
        $guard = (string) config('module-auth.protected_route_guard', 'auth-api');

        DB::transaction(function () use ($guard): void {
            $tenantIds = DB::table('tenants')->where('status', '!=', 'archived')->pluck('id');

            foreach ($tenantIds as $tenantId) {
                foreach (AuditPermission::descriptions() as $name => $description) {
                    $identity = [
                        'tenant_id' => (int) $tenantId,
                        'name' => $name,
                        'guard_name' => $guard,
                    ];
                    $existing = DB::table('permissions')->where($identity)->first([
                        'id',
                        'organization_unit_id',
                        'module',
                        'description',
                        'row_version',
                        'deleted_at',
                    ]);
                    $catalogueValues = [
                        'organization_unit_id' => null,
                        'module' => 'Audit',
                        'description' => $description,
                        'deleted_at' => null,
                    ];

                    if ($existing === null) {
                        DB::table('permissions')->insert([
                            ...$identity,
                            ...$catalogueValues,
                            'row_version' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        continue;
                    }

                    $isCurrent = $existing->organization_unit_id === null
                        && $existing->module === 'Audit'
                        && $existing->description === $description
                        && $existing->deleted_at === null;

                    if (! $isCurrent) {
                        DB::table('permissions')->where('id', $existing->id)->update([
                            ...$catalogueValues,
                            'row_version' => max(1, (int) $existing->row_version) + 1,
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }, 3);
    }
}
