<?php

declare(strict_types=1);

namespace Modules\Workflow\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Workflow\Application\Services\WorkflowService;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WorkflowService.
 *
 * The repository is mocked — no database or Laravel bootstrap required.
 * These tests exercise the delegation and argument-passing logic in the service.
 *
 * Write methods (create, update, delete) use DB::transaction() which requires
 * the Laravel facade; those paths are covered by feature tests.
 * Read methods (list, show, findByEntityType) are tested here.
 */
class WorkflowServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // list — paginate delegation
    // -------------------------------------------------------------------------

    public function test_list_delegates_to_repository_paginate(): void
    {
        $paginator = $this->createMock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

        $repo = $this->createMock(WorkflowRepositoryContract::class);
        $repo->expects($this->once())
            ->method('paginate')
            ->with(15)
            ->willReturn($paginator);

        $service = new WorkflowService($repo);
        $result  = $service->list();

        $this->assertSame($paginator, $result);
    }

    public function test_list_passes_custom_per_page(): void
    {
        $paginator = $this->createMock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

        $repo = $this->createMock(WorkflowRepositoryContract::class);
        $repo->expects($this->once())
            ->method('paginate')
            ->with(25)
            ->willReturn($paginator);

        $service = new WorkflowService($repo);
        $service->list(25);
    }

    // -------------------------------------------------------------------------
    // show — delegates to findOrFail
    // -------------------------------------------------------------------------

    public function test_show_delegates_to_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $repo = $this->createMock(WorkflowRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(42)
            ->willReturn($model);

        $service = new WorkflowService($repo);
        $result  = $service->show(42);

        $this->assertSame($model, $result);
    }

    public function test_show_accepts_string_id(): void
    {
        $model = $this->createMock(Model::class);

        $repo = $this->createMock(WorkflowRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with('uuid-workflow-99')
            ->willReturn($model);

        $service = new WorkflowService($repo);
        $result  = $service->show('uuid-workflow-99');

        $this->assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // findByEntityType — delegates to repository
    // -------------------------------------------------------------------------

    public function test_find_by_entity_type_delegates_to_repository(): void
    {
        $collection = new Collection();

        $repo = $this->createMock(WorkflowRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByEntityType')
            ->with('sales_order')
            ->willReturn($collection);

        $service = new WorkflowService($repo);
        $result  = $service->findByEntityType('sales_order');

        $this->assertSame($collection, $result);
    }

    public function test_find_by_entity_type_returns_collection(): void
    {
        $repo = $this->createMock(WorkflowRepositoryContract::class);
        $repo->method('findByEntityType')->willReturn(new Collection());

        $service = new WorkflowService($repo);
        $result  = $service->findByEntityType('invoice');

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_find_by_entity_type_passes_entity_type_string(): void
    {
        $repo = $this->createMock(WorkflowRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByEntityType')
            ->with('purchase_order')
            ->willReturn(new Collection());

        $service = new WorkflowService($repo);
        $service->findByEntityType('purchase_order');
    }
}
