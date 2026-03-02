<?php

declare(strict_types=1);

namespace Modules\CRM\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Application\Services\CRMService;
use Modules\CRM\Domain\Contracts\CRMRepositoryContract;
use Modules\CRM\Domain\Contracts\CrmLeadRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Structural and delegation tests for CRMService CRUD methods.
 *
 * showOpportunity(), deleteLead(), and listCustomers() method signatures and
 * repository delegation contracts are verified using pure-PHP reflection
 * and mocked repository contracts. No database or Laravel bootstrap required.
 */
class CRMServiceCrudTest extends TestCase
{
    private function makeService(
        ?CRMRepositoryContract $crmRepo = null,
        ?CrmLeadRepositoryContract $leadRepo = null,
    ): CRMService {
        return new CRMService(
            $crmRepo ?? $this->createMock(CRMRepositoryContract::class),
            $leadRepo ?? $this->createMock(CrmLeadRepositoryContract::class),
        );
    }

    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_crm_service_has_show_opportunity_method(): void
    {
        $this->assertTrue(
            method_exists(CRMService::class, 'showOpportunity'),
            'CRMService must expose a public showOpportunity() method.'
        );
    }

    public function test_crm_service_has_delete_lead_method(): void
    {
        $this->assertTrue(
            method_exists(CRMService::class, 'deleteLead'),
            'CRMService must expose a public deleteLead() method.'
        );
    }

    public function test_crm_service_has_list_customers_method(): void
    {
        $this->assertTrue(
            method_exists(CRMService::class, 'listCustomers'),
            'CRMService must expose a public listCustomers() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_show_opportunity_accepts_int_or_string_id(): void
    {
        $reflection = new \ReflectionMethod(CRMService::class, 'showOpportunity');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_delete_lead_accepts_int_or_string_id(): void
    {
        $reflection = new \ReflectionMethod(CRMService::class, 'deleteLead');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_list_customers_has_optional_filters_param(): void
    {
        $reflection = new \ReflectionMethod(CRMService::class, 'listCustomers');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('filters', $params[0]->getName());
        $this->assertTrue($params[0]->isOptional());
    }

    // -------------------------------------------------------------------------
    // showOpportunity — delegates to crmRepository findOrFail
    // -------------------------------------------------------------------------

    public function test_show_opportunity_delegates_to_crm_repository_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $crmRepo = $this->createMock(CRMRepositoryContract::class);
        $crmRepo->expects($this->once())
            ->method('findOrFail')
            ->with(7)
            ->willReturn($model);

        $result = $this->makeService($crmRepo)->showOpportunity(7);

        $this->assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // listCustomers — delegates to crmRepository allCustomers
    // -------------------------------------------------------------------------

    public function test_list_customers_delegates_to_crm_repository_all_customers(): void
    {
        $collection = new Collection();

        $crmRepo = $this->createMock(CRMRepositoryContract::class);
        $crmRepo->expects($this->once())
            ->method('allCustomers')
            ->willReturn($collection);

        $result = $this->makeService($crmRepo)->listCustomers();

        $this->assertSame($collection, $result);
    }

    public function test_list_customers_returns_collection_instance(): void
    {
        $crmRepo = $this->createMock(CRMRepositoryContract::class);
        $crmRepo->method('allCustomers')->willReturn(new Collection(['lead-1', 'lead-2']));

        $result = $this->makeService($crmRepo)->listCustomers([]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    // -------------------------------------------------------------------------
    // deleteLead — return type is bool
    // -------------------------------------------------------------------------

    public function test_delete_lead_return_type_is_bool(): void
    {
        $reflection = new \ReflectionMethod(CRMService::class, 'deleteLead');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame('bool', $returnType);
    }
}
