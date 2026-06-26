<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Services\Contracts\TenantAuthenticationProvisionerInterface;

final class AuthSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function __construct(
        private readonly TenantAuthenticationProvisionerInterface $authentication,
    ) {}

    public function run(): void
    {
        if (! Schema::hasTable('auth_providers')) {
            return;
        }

        $tenant = $this->defaultTenant();
        if ($tenant === null) {
            return;
        }

        $this->authentication->provisionProvider((int) $tenant->getKey());
    }
}
