<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories;

use App\Infrastructure\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Base Repository Unit Tests
 * Validates pagination, filtering, and search behavior.
 */
class BaseRepositoryTest extends TestCase
{
    private BaseRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a concrete test implementation of BaseRepository
        $this->repository = new class(new \App\Domain\Tenant\Models\Tenant()) extends BaseRepository {
            protected array $searchableColumns = ['name'];
            protected array $sortableColumns = ['name', 'created_at'];
            protected array $filterableColumns = ['status'];
        };
    }

    #[Test]
    public function it_paginates_array_data_when_per_page_given(): void
    {
        $data = range(1, 50);

        $result = $this->repository->paginateData($data, ['per_page' => 10, 'page' => 1]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(50, $result->total());
        $this->assertEquals(1, $result->currentPage());
    }

    #[Test]
    public function it_returns_all_data_when_no_per_page(): void
    {
        $data = range(1, 50);

        $result = $this->repository->paginateData($data);

        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }

    #[Test]
    public function it_paginates_collection_data(): void
    {
        $data = collect(array_fill(0, 25, ['name' => 'item']));

        $result = $this->repository->paginateData($data, ['per_page' => 5, 'page' => 2]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(5, $result->items());
        $this->assertEquals(25, $result->total());
        $this->assertEquals(2, $result->currentPage());
    }
}
