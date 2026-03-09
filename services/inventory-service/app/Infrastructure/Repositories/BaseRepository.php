<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Contracts\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as ManualPaginator;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Base Repository - Dynamic, reusable base for all repositories.
 *
 * Supports conditional pagination (per_page triggers pagination, absent = all),
 * filtering, searching, sorting for both Eloquent and arbitrary iterable data.
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;
    protected array $searchableColumns = [];
    protected array $sortableColumns = ['created_at', 'updated_at'];
    protected array $filterableColumns = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function findById(string|int $id, array $relations = []): ?Model
    {
        return $this->model->newQuery()->with($relations)->find($id);
    }

    public function findBy(string $field, mixed $value, array $relations = []): ?Model
    {
        return $this->model->newQuery()->with($relations)->where($field, $value)->first();
    }

    /**
     * Conditional pagination: returns LengthAwarePaginator when per_page is set, Collection otherwise.
     */
    public function getAll(array $params = []): Collection|LengthAwarePaginator
    {
        $query = $this->buildQuery($params);

        if (isset($params['per_page'])) {
            $perPage = (int) $params['per_page'];
            $page = (int) ($params['page'] ?? 1);
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->get();
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(string|int $id, array $data): Model
    {
        $record = $this->findById($id);
        if (!$record) {
            throw new \RuntimeException("Record {$id} not found.");
        }
        $record->update($data);
        return $record->fresh();
    }

    public function delete(string|int $id): bool
    {
        $record = $this->findById($id);
        return $record ? (bool) $record->delete() : false;
    }

    public function restore(string|int $id): bool
    {
        $record = $this->model->newQuery()->withTrashed()->find($id);
        return $record ? (bool) $record->restore() : false;
    }

    /**
     * Paginate any iterable (array, Collection, API response, etc.)
     * Returns LengthAwarePaginator when per_page given, array otherwise.
     */
    public function paginateData(mixed $data, array $params = []): array|LengthAwarePaginator
    {
        $collection = match (true) {
            $data instanceof SupportCollection => $data,
            is_array($data) => collect($data),
            $data instanceof \Traversable => collect(iterator_to_array($data)),
            default => collect((array) $data),
        };

        if (!isset($params['per_page'])) {
            return $collection->all();
        }

        $perPage = (int) $params['per_page'];
        $page = (int) ($params['page'] ?? 1);
        $total = $collection->count();

        return new ManualPaginator(
            $collection->forPage($page, $perPage)->values()->all(),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /**
     * Build Eloquent query with filtering, searching, sorting.
     */
    protected function buildQuery(array $params = []): Builder
    {
        $query = $this->model->newQuery();
        $this->applyTenantScope($query, $params);

        // Search across searchable columns
        if (!empty($params['search']) && !empty($this->searchableColumns)) {
            $search = $params['search'];
            $query->where(function (Builder $q) use ($search) {
                foreach ($this->searchableColumns as $col) {
                    $q->orWhere($col, 'LIKE', "%{$search}%");
                }
            });
        }

        // Filter by allowed columns
        if (!empty($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $col => $val) {
                if (in_array($col, $this->filterableColumns, true)) {
                    is_array($val) ? $query->whereIn($col, $val) : $query->where($col, $val);
                }
            }
        }

        // Direct column filters
        foreach ($this->filterableColumns as $col) {
            if (isset($params[$col]) && $params[$col] !== '') {
                $query->where($col, $params[$col]);
            }
        }

        // Sorting
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDir = strtolower($params['sort_dir'] ?? 'desc');
        if (in_array($sortBy, $this->sortableColumns, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $this->applyCustomScope($query, $params);
    }

    protected function applyTenantScope(Builder $query, array $params): void
    {
        if (isset($params['tenant_id'])) {
            $query->where('tenant_id', $params['tenant_id']);
        }
    }

    protected function applyCustomScope(Builder $query, array $params): Builder
    {
        return $query;
    }
}
