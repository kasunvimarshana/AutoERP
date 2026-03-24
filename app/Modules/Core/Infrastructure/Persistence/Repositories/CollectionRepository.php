<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class CollectionRepository extends BaseRepository
{
    protected Collection $original;

    public function __construct(Collection $collection)
    {
        $this->original = $collection;
        $this->provider = clone $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, array $columns = ['*'])
    {
        $this->applyCriteria();
        $item = $this->provider->firstWhere('id', $id);
        if ($item && $columns !== ['*']) {
            return collect($item)->only($columns)->all();
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $columns = ['*']): Collection
    {
        $this->applyCriteria();
        if ($columns === ['*']) {
            return $this->provider;
        }

        return $this->provider->map(fn ($item) => collect($item)->only($columns)->all());
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
        $total = $this->provider->count();
        $items = $this->provider->slice(($page - 1) * $perPage, $perPage)->values();
        if ($columns !== ['*']) {
            $items = $items->map(fn ($item) => collect($item)->only($columns)->all());
        }

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => $pageName]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        $this->original->push($data);
        $this->resetProvider();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
        $index = $this->original->search(fn ($item) => ($item['id'] ?? null) == $id);
        if ($index !== false) {
            $updated = array_merge($this->original[$index], $data);
            $this->original[$index] = $updated;
            $this->resetProvider();

            return $updated;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id): bool
    {
        $index = $this->original->search(fn ($item) => ($item['id'] ?? null) == $id);
        if ($index !== false) {
            $this->original->forget($index);
            $this->resetProvider();

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyCriteria(): void
    {
        $this->resetProvider();

        // Apply where clauses
        foreach ($this->wheres as $where) {
            $column = $where['column'];
            $operator = $where['operator'];
            $value = $where['value'];

            if ($operator === '=') {
                $this->provider = $this->provider->where($column, $value);
            } elseif ($operator === 'like') {
                $pattern = str_replace('%', '.*', preg_quote($value, '/'));
                $this->provider = $this->provider->filter(fn ($item) => preg_match("/{$pattern}/i", $item[$column] ?? ''));
            } elseif ($operator === '>') {
                $this->provider = $this->provider->filter(fn ($item) => ($item[$column] ?? 0) > $value);
            } elseif ($operator === '>=') {
                $this->provider = $this->provider->filter(fn ($item) => ($item[$column] ?? 0) >= $value);
            } elseif ($operator === '<') {
                $this->provider = $this->provider->filter(fn ($item) => ($item[$column] ?? 0) < $value);
            } elseif ($operator === '<=') {
                $this->provider = $this->provider->filter(fn ($item) => ($item[$column] ?? 0) <= $value);
            }
            // Add more operators as needed
        }

        // Apply whereIn
        foreach ($this->whereIns as $whereIn) {
            $this->provider = $this->provider->whereIn($whereIn['column'], $whereIn['values']);
        }

        // Apply whereBetween
        foreach ($this->whereBetweens as $whereBetween) {
            $this->provider = $this->provider->filter(function ($item) use ($whereBetween) {
                $val = $item[$whereBetween['column']] ?? null;

                return $val >= $whereBetween['values'][0] && $val <= $whereBetween['values'][1];
            });
        }

        // Apply whereNull / whereNotNull
        foreach ($this->whereNulls as $whereNull) {
            $this->provider = $this->provider->filter(function ($item) use ($whereNull) {
                $exists = isset($item[$whereNull['column']]) && ! is_null($item[$whereNull['column']]);

                return $whereNull['not'] ? $exists : ! $exists;
            });
        }

        // Apply orderBy
        foreach ($this->orders as $order) {
            $this->provider = $this->provider->sortBy($order['column'], SORT_REGULAR, $order['direction'] === 'desc');
        }

        // Raw order by is ignored

        // Apply limit & offset
        if ($this->limit !== null) {
            $this->provider = $this->provider->take($this->limit);
        }
        if ($this->offset !== null) {
            $this->provider = $this->provider->slice($this->offset);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function resetProvider(): void
    {
        $this->provider = clone $this->original;
    }
}
