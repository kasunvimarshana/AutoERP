<?php

declare(strict_types=1);

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Services\PurchaseAuthorizationService;

final class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $guard = (string) config('auth.defaults.guard', 'web');

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach (PurchaseAuthorizationService::descriptions() as $name => $description) {
                DB::table('permissions')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'name' => $name, 'guard_name' => $guard],
                    [
                        'module' => 'Purchase',
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
