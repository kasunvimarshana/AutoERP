<?php

declare(strict_types=1);

namespace Modules\CRM\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\CRM\Application\Services\CRMService;
use Modules\CRM\Domain\Contracts\CRMRepositoryContract;
use Modules\CRM\Domain\Contracts\CrmLeadRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CRMService business logic.
 *
 * The repository is stubbed — no database or Laravel bootstrap required.
 * These tests exercise the filter-routing logic inside listOpportunities().
 */
class CRMServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listOpportunities — routing / filter logic
    // -------------------------------------------------------------------------

    public function test_list_opportunities_with_status_filter_calls_find_by_status(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(CRMRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByStatus')
            ->with('open')
            ->willReturn($expected);
        $repo->expects($this->never())->method('findByAssignee');
        $repo->expects($this->never())->method('all');

        $service = new CRMService($repo, $this->createMock(CrmLeadRepositoryContract::class));
        $result  = $service->listOpportunities(['status' => 'open']);

        $this->assertSame($expected, $result);
    }

    public function test_list_opportunities_with_assigned_to_filter_calls_find_by_assignee(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(CRMRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByAssignee')
            ->with(42)
            ->willReturn($expected);
        $repo->expects($this->never())->method('findByStatus');
        $repo->expects($this->never())->method('all');

        $service = new CRMService($repo, $this->createMock(CrmLeadRepositoryContract::class));
        $result  = $service->listOpportunities(['assigned_to' => 42]);

        $this->assertSame($expected, $result);
    }

    public function test_list_opportunities_with_no_filter_calls_all(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(CRMRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($expected);
        $repo->expects($this->never())->method('findByStatus');
        $repo->expects($this->never())->method('findByAssignee');

        $service = new CRMService($repo, $this->createMock(CrmLeadRepositoryContract::class));
        $result  = $service->listOpportunities([]);

        $this->assertSame($expected, $result);
    }

    public function test_list_opportunities_status_filter_takes_priority_over_assignee(): void
    {
        // When both 'status' and 'assigned_to' are present, 'status' wins
        // because the service checks for 'status' first.
        $expected = new Collection();

        $repo = $this->createMock(CRMRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByStatus')
            ->with('won')
            ->willReturn($expected);
        $repo->expects($this->never())->method('findByAssignee');

        $service = new CRMService($repo, $this->createMock(CrmLeadRepositoryContract::class));
        $result  = $service->listOpportunities(['status' => 'won', 'assigned_to' => 7]);

        $this->assertSame($expected, $result);
    }

    public function test_list_opportunities_assigned_to_is_cast_to_int(): void
    {
        // The service casts assigned_to with (int), so string '5' becomes 5.
        $expected = new Collection();

        $repo = $this->createMock(CRMRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByAssignee')
            ->with(5)
            ->willReturn($expected);

        $service = new CRMService($repo, $this->createMock(CrmLeadRepositoryContract::class));
        $service->listOpportunities(['assigned_to' => '5']);
    }

    public function test_list_opportunities_returns_collection_type(): void
    {
        $repo = $this->createMock(CRMRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $service = new CRMService($repo, $this->createMock(CrmLeadRepositoryContract::class));
        $result  = $service->listOpportunities([]);

        $this->assertInstanceOf(Collection::class, $result);
    }
}
