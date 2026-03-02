<?php

declare(strict_types=1);

namespace Modules\CRM\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\CRM\Application\Services\CRMService;
use Modules\CRM\Domain\Contracts\CRMRepositoryContract;
use Modules\CRM\Domain\Contracts\CrmLeadRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CRMService::listLeads().
 *
 * The repositories are stubbed — no database or Laravel bootstrap required.
 * These tests exercise the filter-routing logic inside listLeads().
 */
class CRMServiceLeadTest extends TestCase
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
    // listLeads — no filter → all()
    // -------------------------------------------------------------------------

    public function test_list_leads_with_no_filter_calls_all(): void
    {
        $expected = new Collection();

        $leadRepo = $this->createMock(CrmLeadRepositoryContract::class);
        $leadRepo->expects($this->once())
            ->method('all')
            ->willReturn($expected);
        $leadRepo->expects($this->never())->method('findByStatus');
        $leadRepo->expects($this->never())->method('findByAssignee');

        $result = $this->makeService(leadRepo: $leadRepo)->listLeads([]);

        $this->assertSame($expected, $result);
    }

    public function test_list_leads_returns_collection_type(): void
    {
        $leadRepo = $this->createMock(CrmLeadRepositoryContract::class);
        $leadRepo->method('all')->willReturn(new Collection());

        $result = $this->makeService(leadRepo: $leadRepo)->listLeads([]);

        $this->assertInstanceOf(Collection::class, $result);
    }

    // -------------------------------------------------------------------------
    // listLeads — status filter → findByStatus()
    // -------------------------------------------------------------------------

    public function test_list_leads_with_status_filter_calls_find_by_status(): void
    {
        $expected = new Collection();

        $leadRepo = $this->createMock(CrmLeadRepositoryContract::class);
        $leadRepo->expects($this->once())
            ->method('findByStatus')
            ->with('new')
            ->willReturn($expected);
        $leadRepo->expects($this->never())->method('findByAssignee');
        $leadRepo->expects($this->never())->method('all');

        $result = $this->makeService(leadRepo: $leadRepo)->listLeads(['status' => 'new']);

        $this->assertSame($expected, $result);
    }

    public function test_list_leads_passes_status_string_to_repository(): void
    {
        $leadRepo = $this->createMock(CrmLeadRepositoryContract::class);
        $leadRepo->expects($this->once())
            ->method('findByStatus')
            ->with('qualified')
            ->willReturn(new Collection());

        $this->makeService(leadRepo: $leadRepo)->listLeads(['status' => 'qualified']);
    }

    // -------------------------------------------------------------------------
    // listLeads — assigned_to filter → findByAssignee()
    // -------------------------------------------------------------------------

    public function test_list_leads_with_assigned_to_filter_calls_find_by_assignee(): void
    {
        $expected = new Collection();

        $leadRepo = $this->createMock(CrmLeadRepositoryContract::class);
        $leadRepo->expects($this->once())
            ->method('findByAssignee')
            ->with(10)
            ->willReturn($expected);
        $leadRepo->expects($this->never())->method('findByStatus');
        $leadRepo->expects($this->never())->method('all');

        $result = $this->makeService(leadRepo: $leadRepo)->listLeads(['assigned_to' => 10]);

        $this->assertSame($expected, $result);
    }

    public function test_list_leads_assigned_to_is_cast_to_int(): void
    {
        $leadRepo = $this->createMock(CrmLeadRepositoryContract::class);
        $leadRepo->expects($this->once())
            ->method('findByAssignee')
            ->with(7)
            ->willReturn(new Collection());

        $this->makeService(leadRepo: $leadRepo)->listLeads(['assigned_to' => '7']);
    }

    // -------------------------------------------------------------------------
    // listLeads — status filter takes priority over assigned_to
    // -------------------------------------------------------------------------

    public function test_list_leads_status_filter_takes_priority_over_assigned_to(): void
    {
        $expected = new Collection();

        $leadRepo = $this->createMock(CrmLeadRepositoryContract::class);
        $leadRepo->expects($this->once())
            ->method('findByStatus')
            ->with('contacted')
            ->willReturn($expected);
        $leadRepo->expects($this->never())->method('findByAssignee');

        $result = $this->makeService(leadRepo: $leadRepo)->listLeads([
            'status'      => 'contacted',
            'assigned_to' => 5,
        ]);

        $this->assertSame($expected, $result);
    }
}
