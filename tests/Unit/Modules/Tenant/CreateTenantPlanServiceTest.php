<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Tenant;

use Modules\Tenant\Application\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Application\UseCases\Plans\CreateTenantPlanService;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface;
use PHPUnit\Framework\TestCase;

final class CreateTenantPlanServiceTest extends TestCase
{
    public function testItReturnsConflictWhenSlugAlreadyExists(): void
    {
        $plans = $this->createMock(TenantPlanRepositoryInterface::class);
        $domain = $this->createMock(TenantDomainServiceInterface::class);

        $domain->method('normalizeSlug')->willReturn('starter');
        $plans->method('findBySlug')->willReturn(new \Modules\Core\Application\DTO\DataRecord(['id' => 1]));

        $service = new CreateTenantPlanService($plans, $domain);

        $result = $service->execute([
            'name' => 'Starter',
            'slug' => 'starter',
        ]);

        $this->assertTrue($result->isFailure());
        $this->assertSame('TENANT_CONFLICT', $result->errorOrFail()->code);
    }
}
