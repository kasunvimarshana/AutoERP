<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;

final class SyncTenantPermissionCatalogCommand extends Command
{
    protected $signature = 'tenant:permissions:sync {--tenant= : Optional single tenant ID to synchronize}';

    protected $description = 'Synchronize tenant permission catalogues and Super Admin roles from registered modules.';

    public function handle(
        TenantExecutionContextInterface $executionContext,
        TenantAccessProvisionerInterface $provisioner,
    ): int {
        $tenantOption = $this->option('tenant');
        if ($tenantOption !== null && $tenantOption !== '') {
            $tenantId = (int) $tenantOption;
            if ($tenantId < 1) {
                $this->error('Tenant ID must be a positive integer.');

                return self::FAILURE;
            }

            $exists = DB::table('tenants')
                ->where('id', $tenantId)
                ->where('status', '!=', 'archived')
                ->exists();
            if (! $exists) {
                $this->error("Active or non-archived tenant [{$tenantId}] was not found.");

                return self::FAILURE;
            }

            $result = $executionContext->runForTenant(
                $tenantId,
                static fn (): array => $provisioner->provision($tenantId),
            );
            $this->info(sprintf(
                'Tenant [%d] permissions synchronized: %d active permissions.',
                $tenantId,
                $result['permission_count'],
            ));

            return self::SUCCESS;
        }

        $count = 0;
        $permissionCount = 0;
        DB::table('tenants')
            ->where('status', '!=', 'archived')
            ->orderBy('id')
            ->pluck('id')
            ->each(static function ($tenantId) use ($executionContext, $provisioner, &$count, &$permissionCount): void {
                $tenantId = (int) $tenantId;
                $result = $executionContext->runForTenant(
                    $tenantId,
                    static fn (): array => $provisioner->provision($tenantId),
                );
                $count++;
                $permissionCount = (int) $result['permission_count'];
            });

        $this->info(sprintf(
            'Synchronized %d tenant(s) with %d active permission(s) each.',
            $count,
            $permissionCount,
        ));

        return self::SUCCESS;
    }
}
