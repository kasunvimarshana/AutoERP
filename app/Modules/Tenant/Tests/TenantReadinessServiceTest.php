<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use DateTimeImmutable;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantOrganizationUnitGatewayInterface;
use Modules\Tenant\Services\TenantReadinessService;
use Modules\Tenant\Services\TenantSubscriptionWindowPolicy;
use PHPUnit\Framework\TestCase;

final class TenantReadinessServiceTest extends TestCase
{
    public function test_ready_tenant_passes_every_activation_check(): void
    {
        $service = $this->service(
            $this->tenant(),
            $this->primaryDomain(),
            $this->rootOrganizationUnit(),
        );

        $result = $service->get(10);

        self::assertTrue($result->isSuccess());
        $readiness = $result->valueOrFail();
        self::assertTrue($readiness->readyForActivation());
        self::assertSame([], $readiness->blockingMessages());
    }

    public function test_incomplete_tenant_returns_actionable_blocking_checks(): void
    {
        $service = $this->service(
            $this->tenant([
                'tenant_plan_id' => null,
                'plan' => null,
                'base_currency_id' => null,
                'base_currency' => null,
                'trial_ends_at' => '2000-01-01 00:00:00',
            ]),
            null,
            null,
        );

        $readiness = $service->get(10)->valueOrFail();
        $payload = $readiness->toArray();

        self::assertFalse($payload['ready_for_activation']);
        self::assertSame(
            [
                'subscription_plan',
                'base_currency',
                'root_organization_unit',
                'primary_domain',
                'subscription_window',
            ],
            array_column(
                array_values(array_filter(
                    $payload['checks'],
                    static fn (array $check): bool => ! $check['ready'],
                )),
                'key',
            ),
        );
        self::assertCount(5, $readiness->blockingMessages());
    }

    public function test_missing_tenant_returns_not_found_result(): void
    {
        $service = $this->service(null, null, null);

        $result = $service->get(999);

        self::assertTrue($result->isFailure());
        self::assertSame(TenantErrorCode::NOT_FOUND, $result->errorOrFail()->code);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function tenant(array $overrides = []): DataRecord
    {
        return new DataRecord(array_replace([
            'id' => 10,
            'tenant_plan_id' => 20,
            'plan' => ['id' => 20, 'is_active' => true],
            'base_currency_id' => 30,
            'base_currency' => ['id' => 30, 'is_active' => true],
            'subscription_ends_at' => '2999-12-31 23:59:59',
            'trial_ends_at' => null,
        ], $overrides));
    }

    private function primaryDomain(): DataRecord
    {
        return new DataRecord([
            'id' => 40,
            'tenant_id' => 10,
            'domain' => 'tenant.example.com',
            'is_primary' => true,
            'status' => 'active',
            'verified_at' => '2026-01-01 00:00:00',
        ]);
    }

    private function rootOrganizationUnit(): DataRecord
    {
        return new DataRecord([
            'id' => 50,
            'tenant_id' => 10,
            'parent_id' => null,
            'path' => '/50/',
            'depth' => 0,
            'is_active' => true,
        ]);
    }

    private function service(
        ?DataRecord $tenant,
        ?DataRecord $domain,
        ?DataRecord $root,
    ): TenantReadinessService {
        $tenants = $this->createMock(TenantRepositoryInterface::class);
        $tenants->method('findById')->willReturn($tenant);
        $domains = $this->createMock(TenantDomainRepositoryInterface::class);
        $domains->method('findPrimaryByTenant')->willReturn($domain);
        $organizationUnits = $this->createMock(TenantOrganizationUnitGatewayInterface::class);
        $organizationUnits->method('findRoot')->willReturn($root);

        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-06-25 00:00:00 UTC'));

        return new TenantReadinessService(
            $tenants,
            $domains,
            $organizationUnits,
            new TenantSubscriptionWindowPolicy($clock),
        );
    }
}
