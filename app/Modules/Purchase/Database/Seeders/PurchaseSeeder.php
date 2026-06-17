<?php

declare(strict_types=1);

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Purchase\Services\PurchaseAuthorizationService;

final class PurchaseSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $tenant = $this->defaultTenant();
        if ($tenant === null) {
            return;
        }

        $tenantId = (int) $tenant->getKey();
        $guard = (string) config('auth.defaults.guard', 'web');

        foreach (PurchaseAuthorizationService::descriptions() as $name => $description) {
            DB::table('permissions')->updateOrInsert(
                ['tenant_id' => $tenantId, 'name' => $name, 'guard_name' => $guard],
                [
                    'organization_unit_id' => null,
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
