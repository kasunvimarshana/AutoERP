<?php

declare(strict_types=1);

namespace Modules\Reporting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Reporting\Services\ReportingAuthorizationService;

final class ReportingSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $guard = (string) config('auth.defaults.guard', 'web');

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach (ReportingAuthorizationService::descriptions() as $name => $description) {
                DB::table('permissions')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'name' => $name, 'guard_name' => $guard],
                    [
                        'organization_unit_id' => null,
                        'module' => 'Reporting',
                        'description' => $description,
                        'row_version' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }
}
