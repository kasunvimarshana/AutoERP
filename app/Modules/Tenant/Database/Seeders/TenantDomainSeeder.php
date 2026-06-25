<?php

declare(strict_types=1);

namespace Modules\Tenant\Database\Seeders;

use Database\Seeders\Concerns\ResolvesSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Constants\TenantDomainCheckStatus;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantDomainOwnershipStatus;
use Modules\Tenant\Constants\TenantDomainStatus;
use Modules\Tenant\Models\TenantDomainModel;
use Modules\Tenant\Models\TenantPrimaryDomainModel;

final class TenantDomainSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Synthetic tenant domain verification is restricted to local and testing environments.');
        }

        if (
            ! Schema::hasTable('tenant_domains')
            || ! Schema::hasTable('tenant_primary_domains')
        ) {
            return;
        }

        $tenant = $this->defaultTenant();
        if ($tenant === null) {
            return;
        }

        $domains = $this->configuredDomains();
        if ($domains === []) {
            return;
        }

        $tenantId = (int) $tenant->getKey();
        app(TenantExecutionContextInterface::class)->runForTenant(
            $tenantId,
            function () use ($tenantId, $domains): void {
                DB::transaction(function () use ($tenantId, $domains): void {
                    $primaryDomainId = null;

                    foreach ($domains as $index => $domain) {
                        $record = TenantDomainModel::query()->updateOrCreate(
                            ['domain' => $domain],
                            [
                                'tenant_id' => $tenantId,
                                'status' => TenantDomainStatus::ACTIVE,
                                'ownership_status' => TenantDomainOwnershipStatus::VERIFIED,
                                'routing_status' => TenantDomainCheckStatus::READY,
                                'tls_status' => TenantDomainCheckStatus::READY,
                                'reachability_status' => TenantDomainCheckStatus::READY,
                                'operational_status' => TenantDomainOperationalStatus::READY,
                                'verification_method' => 'dns_txt',
                                'verified_token_hash' => hash('sha256', 'local-testing-domain-proof'),
                                'verified_at' => now(),
                                'last_verified_at' => now(),
                                'last_operational_check_at' => now(),
                                'row_version' => 1,
                            ],
                        );

                        if ($index === 0) {
                            $primaryDomainId = (int) $record->getKey();
                        }
                    }

                    if ($primaryDomainId !== null) {
                        TenantPrimaryDomainModel::query()->updateOrCreate(
                            ['tenant_id' => $tenantId],
                            [
                                'tenant_domain_id' => $primaryDomainId,
                                'row_version' => 1,
                            ],
                        );
                    }
                }, 3);
            },
        );
    }

    /** @return list<string> */
    private function configuredDomains(): array
    {
        $configured = trim((string) env(
            'AUTOERP_TENANT_DOMAINS',
            'localhost,127.0.0.1,autoerp.local,autoerp.test',
        ));

        $domains = array_map(
            static fn (string $value): string => strtolower(rtrim(trim($value), '.')),
            explode(',', $configured),
        );

        return array_values(array_unique(array_filter($domains)));
    }
}
