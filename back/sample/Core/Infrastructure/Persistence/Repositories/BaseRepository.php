<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The data provider (e.g., Builder, Collection, ApiClient).
     */
    protected mixed $provider;

    /**
     * The relationships to eager load.
     */
    protected array $with = [];

    /**
     * Where clauses.
     */
    protected array $wheres = [];

    /**
     * WhereIn clauses.
     */
    protected array $whereIns = [];

    /**
     * WhereBetween clauses.
     */
    protected array $whereBetweens = [];

    /**
     * WhereNull clauses.
     */
    protected array $whereNulls = [];

    /**
     * Order by clauses.
     */
    protected array $orders = [];

    /**
     * Raw order by clauses.
     */
    protected array $orderByRaw = [];

    /**
     * The limit value.
     */
    protected ?int $limit = null;

    /**
     * The offset value.
     */
    protected ?int $offset = null;

    /**
     * Optional mapper that converts provider items into Domain entities.
     */
    protected $domainEntityMapper = null;

    /**
     * Configure a mapper for converting provider items to Domain entities.
     */
    protected function setDomainEntityMapper(callable $mapper): void
    {
        $this->domainEntityMapper = $mapper;
    }

    /**
     * Convert a single item to a Domain entity when a mapper is configured.
     */
    protected function mapToDomainEntity(mixed $item): mixed
    {
        if ($item === null || $this->domainEntityMapper === null) {
            return $item;
        }

        return ($this->domainEntityMapper)($item);
    }

    /**
     * Convert a collection of items to Domain entities.
     */
    protected function mapCollectionToDomainEntities(Collection $items): Collection
    {
        return $items->map(fn (mixed $item) => $this->mapToDomainEntity($item));
    }

    /**
     * Convert paginated items to Domain entities.
     */
    protected function mapPaginatorToDomainEntities(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        return $paginator->through(fn (mixed $item) => $this->mapToDomainEntity($item));
    }

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
    public function resetCriteria(): static
    {
        $this->with = [];
        $this->wheres = [];
        $this->whereIns = [];
        $this->whereBetweens = [];
        $this->whereNulls = [];
        $this->orders = [];
        $this->orderByRaw = [];
        $this->limit = null;
        $this->offset = null;

        $this->resetProvider();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function find(int|string $id, array $columns = ['*']): mixed
    {
        try {
            $this->applyCriteria();

            return $this->mapToDomainEntity($this->provider->find($id, $columns));
        } finally {
            $this->resetCriteria();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $columns = ['*']): Collection
    {
        try {
            $this->applyCriteria();

            return $this->mapCollectionToDomainEntities($this->provider->get($columns));
        } finally {
            $this->resetCriteria();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('core.pagination.per_page', 15);
        $pageName = $pageName ?? config('core.pagination.page_name', 'page');

        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        try {
            $this->applyCriteria();

            return $this->mapPaginatorToDomainEntities(
                $this->provider->paginate($perPage, $columns, $pageName, $page)
            );
        } finally {
            $this->resetCriteria();
        }
    }

    /**
     * Apply all stored criteria to the provider.
     */
    abstract protected function applyCriteria(): void;

    /**
     * Reset the provider to its initial state.
     */
    abstract protected function resetProvider(): void;
}
