<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Constants\TenantStatus;

final class TenantPermissionCatalogueSeeder extends Seeder
{
    public function __construct(
        private readonly TenantAccessProvisionerInterface $provisioner,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function run(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasTable('permissions')) {
            return;
        }

        $tenantIds = DB::table('tenants')
            ->where('status', '!=', TenantStatus::ARCHIVED)
            ->orderBy('id')
            ->pluck('id');

        foreach ($tenantIds as $tenantId) {
            $this->executionContext->runForTenant(
                (int) $tenantId,
                fn (): array => $this->provisioner->provision((int) $tenantId),
            );
        }
    }
}
