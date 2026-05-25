<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Tenant;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Application\UseCases\Domains\TenantDomainService;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface;
use PHPUnit\Framework\TestCase;

final class TenantDomainCrudServiceTest extends TestCase
{
    public function testItClearsPreviousPrimaryWhenCreatingPrimaryDomain(): void
    {
        $domains = $this->createMock(TenantDomainRepositoryInterface::class);
        $tenants = $this->createMock(TenantRepositoryInterface::class);
        $rules = $this->createMock(TenantDomainServiceInterface::class);

        $tenants->method('findById')->willReturn(new DataRecord(['id' => 7]));
        $rules->method('normalizeDomain')->willReturn('acme.example.com');
        $rules->method('normalizeMetadata')->willReturn([]);

        $domains->method('findByDomain')->willReturn(null);
        $domains->expects($this->once())->method('clearPrimaryForTenant')->with(7);
        $domains->method('create')->willReturn(new DataRecord([
            'id' => 10,
            'tenant_id' => 7,
            'domain' => 'acme.example.com',
            'is_primary' => true,
            'is_verified' => false,
            'verified_at' => null,
        ]));

        $service = new TenantDomainService($domains, $tenants, $rules);

        $result = $service->create([
            'tenant_id' => 7,
            'domain' => 'acme.example.com',
            'is_primary' => true,
        ]);

        $this->assertTrue($result->isSuccess());
    }
}
