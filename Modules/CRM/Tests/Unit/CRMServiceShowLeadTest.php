<?php

declare(strict_types=1);

namespace Modules\CRM\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Application\Services\CRMService;
use Modules\CRM\Domain\Contracts\CRMRepositoryContract;
use Modules\CRM\Domain\Contracts\CrmLeadRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CRMService::showLead().
 *
 * Verifies that showLead() delegates to the lead repository's findOrFail()
 * and returns the model as-is. No database or Laravel bootstrap required.
 */
class CRMServiceShowLeadTest extends TestCase
{
    private function makeService(
        ?CRMRepositoryContract $oppRepo = null,
        ?CrmLeadRepositoryContract $leadRepo = null,
    ): CRMService {
        return new CRMService(
            $oppRepo  ?? $this->createMock(CRMRepositoryContract::class),
            $leadRepo ?? $this->createMock(CrmLeadRepositoryContract::class),
        );
    }

    // -------------------------------------------------------------------------
    // showLead — delegates to leadRepository->findOrFail()
    // -------------------------------------------------------------------------

    public function test_show_lead_delegates_to_lead_repository_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $leadRepo = $this->createMock(CrmLeadRepositoryContract::class);
        $leadRepo->expects($this->once())
            ->method('findOrFail')
            ->with(42)
            ->willReturn($model);

        $result = $this->makeService(leadRepo: $leadRepo)->showLead(42);

        $this->assertSame($model, $result);
    }

    public function test_show_lead_passes_integer_id_to_repository(): void
    {
        $model = $this->createMock(Model::class);

        $leadRepo = $this->createMock(CrmLeadRepositoryContract::class);
        $leadRepo->expects($this->once())
            ->method('findOrFail')
            ->with(99)
            ->willReturn($model);

        $this->makeService(leadRepo: $leadRepo)->showLead(99);
    }

    public function test_show_lead_returns_model_from_repository(): void
    {
        $model = $this->createMock(Model::class);

        $leadRepo = $this->createMock(CrmLeadRepositoryContract::class);
        $leadRepo->method('findOrFail')->willReturn($model);

        $result = $this->makeService(leadRepo: $leadRepo)->showLead(1);

        $this->assertInstanceOf(Model::class, $result);
    }

    // -------------------------------------------------------------------------
    // showLead — method existence and signature
    // -------------------------------------------------------------------------

    public function test_crm_service_has_show_lead_method(): void
    {
        $this->assertTrue(
            method_exists(CRMService::class, 'showLead'),
            'CRMService must expose a public showLead() method.'
        );
    }

    public function test_show_lead_accepts_int_parameter(): void
    {
        $reflection = new \ReflectionMethod(CRMService::class, 'showLead');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }
}
