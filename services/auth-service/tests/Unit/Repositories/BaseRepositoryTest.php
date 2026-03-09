<?php

namespace Tests\Unit\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Concrete implementation of BaseRepository for testing.
 */
class ConcreteRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return ConcreteModel::class;
    }

    protected function getDefaultSearchFields(): array
    {
        return ['name', 'email'];
    }

    protected function getFilterableColumns(): array
    {
        return ['id', 'name', 'email', 'status', 'tenant_id', 'created_at'];
    }
}

/**
 * Minimal Eloquent model for testing.
 */
class ConcreteModel extends Model
{
    protected $table = 'users';
    protected $fillable = ['id', 'name', 'email', 'status', 'tenant_id', 'created_at'];
}

class BaseRepositoryTest extends TestCase
{
    private ConcreteRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ConcreteRepository();
    }

    // -----------------------------------------------------------------------
    // Pagination Tests
    // -----------------------------------------------------------------------

    /** @test */
    public function it_returns_paginator_when_per_page_is_provided(): void
    {
        // Build a mocked query that returns a paginator
        $paginator = new LengthAwarePaginator(
            items: collect([['id' => 1], ['id' => 2]]),
            total: 10,
            perPage: 2,
            currentPage: 1
        );

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('with')->andReturnSelf();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('orWhereRaw')->andReturnSelf();
        $query->shouldReceive('orderBy')->andReturnSelf();
        $query->shouldReceive('paginate')
              ->with(2, ['*'], 'page', 1)
              ->andReturn($paginator);

        $result = $this->invokeApplyParamsAndExecute($query, ['per_page' => 2, 'page' => 1]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->total());
    }

    /** @test */
    public function it_returns_collection_when_no_per_page_given(): void
    {
        $collection = collect([['id' => 1], ['id' => 2], ['id' => 3]]);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('with')->andReturnSelf();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('orWhereRaw')->andReturnSelf();
        $query->shouldReceive('orderBy')->andReturnSelf();
        $query->shouldReceive('get')->andReturn($collection);

        $result = $this->invokeApplyParamsAndExecute($query, []);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
    }

    /** @test */
    public function it_caps_per_page_at_200(): void
    {
        $paginator = new LengthAwarePaginator(collect([]), 0, 200, 1);

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('with')->andReturnSelf();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('orWhereRaw')->andReturnSelf();
        $query->shouldReceive('orderBy')->andReturnSelf();
        $query->shouldReceive('paginate')
              ->with(200, ['*'], 'page', 1)  // capped at 200
              ->andReturn($paginator);

        $this->invokeApplyParamsAndExecute($query, ['per_page' => 99999]);
    }

    // -----------------------------------------------------------------------
    // Filtering Tests
    // -----------------------------------------------------------------------

    /** @test */
    public function it_applies_equality_filter(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')->with('status', '=', 'active')->once()->andReturnSelf();

        $this->invokeApplyFilters($query, [
            'filters' => [
                ['field' => 'status', 'operator' => '=', 'value' => 'active'],
            ],
        ]);
    }

    /** @test */
    public function it_applies_in_filter(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereIn')->with('status', ['active', 'pending'])->once()->andReturnSelf();

        $this->invokeApplyFilters($query, [
            'filters' => [
                ['field' => 'status', 'operator' => 'in', 'value' => ['active', 'pending']],
            ],
        ]);
    }

    /** @test */
    public function it_applies_between_filter(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereBetween')->with('created_at', ['2024-01-01', '2024-12-31'])->once()->andReturnSelf();

        $this->invokeApplyFilters($query, [
            'filters' => [
                ['field' => 'created_at', 'operator' => 'between', 'value' => ['2024-01-01', '2024-12-31']],
            ],
        ]);
    }

    /** @test */
    public function it_rejects_non_whitelisted_filter_columns(): void
    {
        $query = Mockery::mock(Builder::class);
        // 'password' is NOT in the filterable columns - should not call where()
        $query->shouldNotReceive('where');

        $this->invokeApplyFilters($query, [
            'filters' => [
                ['field' => 'password', 'operator' => '=', 'value' => 'secret'],
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // Search Tests
    // -----------------------------------------------------------------------

    /** @test */
    public function it_applies_full_text_search_across_multiple_columns(): void
    {
        $innerQuery = Mockery::mock(Builder::class);
        $innerQuery->shouldReceive('orWhereRaw')
                   ->with('LOWER(name::text) LIKE ?', ['%john%'])
                   ->once()
                   ->andReturnSelf();
        $innerQuery->shouldReceive('orWhereRaw')
                   ->with('LOWER(email::text) LIKE ?', ['%john%'])
                   ->once()
                   ->andReturnSelf();

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('where')->with(Mockery::type(\Closure::class))->andReturnUsing(
            function ($callback) use ($innerQuery, $query) {
                $callback($innerQuery);
                return $query;
            }
        );

        $this->invokeApplySearch($query, [
            'search'        => 'John',
            'search_fields' => ['name', 'email'],
        ]);
    }

    /** @test */
    public function it_skips_search_when_no_term_given(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldNotReceive('where');

        $this->invokeApplySearch($query, []);
    }

    // -----------------------------------------------------------------------
    // Sorting Tests
    // -----------------------------------------------------------------------

    /** @test */
    public function it_applies_sorting(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('orderBy')->with('name', 'asc')->once()->andReturnSelf();

        $this->invokeApplySorting($query, ['sort_by' => 'name', 'sort_direction' => 'asc']);
    }

    /** @test */
    public function it_defaults_to_desc_for_invalid_sort_direction(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('orderBy')->with('name', 'desc')->once()->andReturnSelf();

        $this->invokeApplySorting($query, ['sort_by' => 'name', 'sort_direction' => 'INVALID']);
    }

    /** @test */
    public function it_does_not_sort_by_non_whitelisted_column(): void
    {
        $query = Mockery::mock(Builder::class);
        // 'password' not in filterable columns — should fall back to default sort
        $query->shouldReceive('orderBy')->with('created_at', 'desc')->once()->andReturnSelf();

        $this->invokeApplySorting($query, ['sort_by' => 'password']);
    }

    // -----------------------------------------------------------------------
    // Helper: invoke private methods via reflection
    // -----------------------------------------------------------------------

    private function invokeApplyParamsAndExecute(Builder $query, array $params): mixed
    {
        $reflection = new \ReflectionMethod(BaseRepository::class, 'applyParamsAndExecute');
        $reflection->setAccessible(true);
        return $reflection->invoke($this->repo, $query, $params);
    }

    private function invokeApplyFilters(Builder $query, array $params): Builder
    {
        $reflection = new \ReflectionMethod(BaseRepository::class, 'applyFilters');
        $reflection->setAccessible(true);
        return $reflection->invoke($this->repo, $query, $params);
    }

    private function invokeApplySearch(Builder $query, array $params): Builder
    {
        $reflection = new \ReflectionMethod(BaseRepository::class, 'applySearch');
        $reflection->setAccessible(true);
        return $reflection->invoke($this->repo, $query, $params);
    }

    private function invokeApplySorting(Builder $query, array $params): Builder
    {
        $reflection = new \ReflectionMethod(BaseRepository::class, 'applySorting');
        $reflection->setAccessible(true);
        return $reflection->invoke($this->repo, $query, $params);
    }
}
