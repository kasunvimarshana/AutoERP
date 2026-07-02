<?php

declare(strict_types=1);

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final class TenantPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $executionContext = app(TenantExecutionContextInterface::class);
        $provisioner = app(TenantAccessProvisionerInterface::class);

        DB::table('tenants')
            ->where('status', '!=', 'archived')
            ->orderBy('id')
            ->pluck('id')
            ->each(static function ($tenantId) use ($executionContext, $provisioner): void {
                $tenantId = (int) $tenantId;

                $executionContext->runForTenant(
                    $tenantId,
                    static fn (): array => $provisioner->provision($tenantId),
                );
            });
    }
}
