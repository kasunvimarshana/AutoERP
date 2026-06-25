<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Database\Seeders;

use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Services\Contracts\TenantOrganizationProvisionerInterface;

final class OrganizationUnitSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function __construct(
        private readonly TenantOrganizationProvisionerInterface $organizations,
    ) {}

    public function run(): void
    {
        if (! Schema::hasTable('organization_units') || ! Schema::hasTable('organization_unit_types')) {
            return;
        }

        $tenant = $this->defaultTenant();
        if ($tenant === null) {
            return;
        }

        $this->organizations->provision(
            tenantId: (int) $tenant->getKey(),
            tenantCode: (string) $tenant->getAttribute('code'),
            tenantName: (string) $tenant->getAttribute('name'),
        );
    }
}
