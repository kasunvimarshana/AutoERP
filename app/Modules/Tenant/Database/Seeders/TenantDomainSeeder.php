<?php

declare(strict_types=1);

namespace Modules\Tenant\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Tenant\Models\TenantDomainModel;

final class TenantDomainSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('tenant_domains')) {
            return;
        }

        $tenant = $this->defaultTenant();
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant): void {
            $domains = ['localhost', '127.0.0.1', 'autoerp.local', 'autoerp.test'];

            foreach ($domains as $index => $domain) {
                TenantDomainModel::query()->updateOrCreate(
                    ['domain' => $domain],
                    [
                        'tenant_id' => $tenant->getKey(),
                        'is_primary' => $index === 0,
                        'is_verified' => true,
                        'verified_at' => now(),
                        'status' => 'active',
                        'row_version' => 1,
                        'metadata' => ['seed_source' => 'tenant_module'],
                    ],
                );
            }
        }, 3);
    }
}
