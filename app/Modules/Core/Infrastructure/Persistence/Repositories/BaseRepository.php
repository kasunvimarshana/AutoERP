<?php

namespace Modules\Core\Infrastructure\Persistence\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;

abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The data provider (e.g., Builder, Collection, ApiClient).
     *
     * @var mixed
     */
    protected $provider;

    /**
     * The relationships to eager load.
     *
     * @var array
     */
    protected array $with = [];

    /**
     * Where clauses.
     *
     * @var array
     */
    protected array $wheres = [];

    /**
     * WhereIn clauses.
     *
     * @var array
     */
    protected array $whereIns = [];

    /**
     * WhereBetween clauses.
     *
     * @var array
     */
    protected array $whereBetweens = [];

    /**
     * WhereNull clauses.
     *
     * @var array
     */
    protected array $whereNulls = [];

    /**
     * Order by clauses.
     *
     * @var array
     */
    protected array $orders = [];

    /**
     * Raw order by clauses.
     *
     * @var array
     */
    protected array $orderByRaw = [];

    /**
     * The limit value.
     *
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * The offset value.
     *
     * @var int|null
     */
    protected ?int $offset = null;

    /**
     * {@inheritdoc}
     */
    public function with(array|string $relations): static
    {
        $this->with = array_merge($this->with, (array) $relations);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('column', 'operator', 'value', 'boolean');
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereIn(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        $this->whereIns[] = compact('column', 'values', 'boolean', 'not');
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        $this->whereBetweens[] = compact('column', 'values', 'boolean', 'not');
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function whereNull(string $column, string $boolean = 'and', bool $not = false): static
    {
        $this->whereNulls[] = compact('column', 'boolean', 'not');
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function orderByRaw(string $sql, array $bindings = []): static
    {
        $this->orderByRaw[] = compact('sql', 'bindings');
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, array $columns = ['*'])
    {
        $this->applyCriteria();
        return $this->provider->find($id, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $columns = ['*']): Collection
    {
        $this->applyCriteria();
        return $this->provider->get($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('core.pagination.per_page', 15);
        $pageName = $pageName ?? config('core.pagination.page_name', 'page');

        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $this->applyCriteria();
        return $this->provider->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Apply all stored criteria to the provider.
     *
     * @return void
     */
    abstract protected function applyCriteria(): void;

    /**
     * Reset the provider to its initial state.
     *
     * @return void
     */
    abstract protected function resetProvider(): void;
}
