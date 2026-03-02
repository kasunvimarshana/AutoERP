<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Unit;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenancy\Application\Services\TenancyService;
use Modules\Tenancy\Domain\Contracts\TenantRepositoryContract;
use Modules\Tenancy\Domain\Entities\Tenant;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TenancyService.
 *
 * Repositories are mocked — no database or Laravel bootstrap required.
 * Write-path methods (create, update, delete) use DB::transaction() which
 * requires the Laravel facade and are covered by feature tests.
 * These tests exercise delegation and argument-passing logic for read paths.
 */
class TenancyServiceTest extends TestCase
{
    private function makeService(?TenantRepositoryContract $repo = null): TenancyService
    {
        return new TenancyService(
            $repo ?? $this->createMock(TenantRepositoryContract::class)
        );
    }

    // -------------------------------------------------------------------------
    // list — delegates to repository paginate
    // -------------------------------------------------------------------------

    public function test_list_delegates_to_repository_paginate_with_default_per_page(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $repo = $this->createMock(TenantRepositoryContract::class);
        $repo->expects($this->once())
            ->method('paginate')
            ->with(15)
            ->willReturn($paginator);

        $result = $this->makeService($repo)->list();

        $this->assertSame($paginator, $result);
    }

    public function test_list_passes_custom_per_page_to_paginate(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $repo = $this->createMock(TenantRepositoryContract::class);
        $repo->expects($this->once())
            ->method('paginate')
            ->with(50)
            ->willReturn($paginator);

        $this->makeService($repo)->list(50);
    }

    // -------------------------------------------------------------------------
    // listActive — delegates to allActive
    // -------------------------------------------------------------------------

    public function test_list_active_delegates_to_all_active(): void
    {
        $collection = new Collection();

        $repo = $this->createMock(TenantRepositoryContract::class);
        $repo->expects($this->once())
            ->method('allActive')
            ->willReturn($collection);

        $result = $this->makeService($repo)->listActive();

        $this->assertSame($collection, $result);
    }

    public function test_list_active_returns_collection_type(): void
    {
        $repo = $this->createMock(TenantRepositoryContract::class);
        $repo->method('allActive')->willReturn(new Collection());

        $result = $this->makeService($repo)->listActive();

        $this->assertInstanceOf(Collection::class, $result);
    }

    // -------------------------------------------------------------------------
    // show — delegates to findOrFail
    // -------------------------------------------------------------------------

    public function test_show_delegates_to_find_or_fail(): void
    {
        $model = $this->createMock(Model::class);

        $repo = $this->createMock(TenantRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(1)
            ->willReturn($model);

        $result = $this->makeService($repo)->show(1);

        $this->assertSame($model, $result);
    }

    public function test_show_accepts_string_id(): void
    {
        $model = $this->createMock(Model::class);

        $repo = $this->createMock(TenantRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with('uuid-tenant-1')
            ->willReturn($model);

        $result = $this->makeService($repo)->show('uuid-tenant-1');

        $this->assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // findBySlug — delegates to repository
    // -------------------------------------------------------------------------

    public function test_find_by_slug_delegates_to_repository(): void
    {
        $tenant = $this->createMock(Tenant::class);

        $repo = $this->createMock(TenantRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findBySlug')
            ->with('acme')
            ->willReturn($tenant);

        $result = $this->makeService($repo)->findBySlug('acme');

        $this->assertSame($tenant, $result);
    }

    public function test_find_by_slug_returns_null_when_not_found(): void
    {
        $repo = $this->createMock(TenantRepositoryContract::class);
        $repo->method('findBySlug')->willReturn(null);

        $result = $this->makeService($repo)->findBySlug('nonexistent');

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // findByDomain — delegates to repository
    // -------------------------------------------------------------------------

    public function test_find_by_domain_delegates_to_repository(): void
    {
        $tenant = $this->createMock(Tenant::class);

        $repo = $this->createMock(TenantRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByDomain')
            ->with('acme.example.com')
            ->willReturn($tenant);

        $result = $this->makeService($repo)->findByDomain('acme.example.com');

        $this->assertSame($tenant, $result);
    }

    public function test_find_by_domain_returns_null_when_not_found(): void
    {
        $repo = $this->createMock(TenantRepositoryContract::class);
        $repo->method('findByDomain')->willReturn(null);

        $result = $this->makeService($repo)->findByDomain('unknown.example.com');

        $this->assertNull($result);
    }
}
