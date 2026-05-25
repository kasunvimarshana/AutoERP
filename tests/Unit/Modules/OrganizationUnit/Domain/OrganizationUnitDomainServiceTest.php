<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\OrganizationUnit\Domain;

use Modules\OrganizationUnit\Domain\Services\OrganizationUnitDomainService;
use PHPUnit\Framework\TestCase;

final class OrganizationUnitDomainServiceTest extends TestCase
{
    public function test_normalize_name_trims_and_returns_value(): void
    {
        $service = new OrganizationUnitDomainService();

        self::assertSame('Head Office', $service->normalizeName('  Head Office  '));
    }

    public function test_ensure_tenant_id_rejects_invalid_value(): void
    {
        $service = new OrganizationUnitDomainService();

        $this->expectException(\InvalidArgumentException::class);
        $service->ensureTenantId(0);
    }
}
