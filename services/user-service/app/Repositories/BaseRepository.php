<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    protected array $eagerLoads = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    // -------------------------------------------------------------------------
    // Query builder helpers
    // -------------------------------------------------------------------------

    protected function newQuery(): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($this->eagerLoads)) {
            $query->with($this->eagerLoads);
        }

        return $query;
    }

    // -------------------------------------------------------------------------
    // BaseRepositoryInterface
    // -------------------------------------------------------------------------

    public function all(array $columns = ['*']): Collection
    {
        return $this->newQuery()->get($columns);
    }

    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->newQuery()->find($id, $columns);
    }

    public function findBy(string $column, mixed $value, array $columns = ['*']): Collection
    {
        return $this->newQuery()->where($column, $value)->get($columns);
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int|string $id, array $data): Model
    {
        $record = $this->newQuery()->findOrFail($id);
        $record->fill($data)->save();

        return $record->fresh();
    }

    public function delete(int|string $id): bool
    {
        $record = $this->newQuery()->findOrFail($id);

        return (bool) $record->delete();
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->newQuery()->paginate($perPage, $columns);
    }

    /**
     * Search across multiple columns using LIKE.
     */
    public function search(string $term, array $columns): Collection
    {
        $query = $this->newQuery();

        $query->where(function (Builder $q) use ($term, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', '%' . $term . '%');
            }
        });

        return $query->get();
    }

    /**
     * Apply an associative array of where-clause filters.
     * Each key may use dot-notation to denote operator, e.g. 'age:gte' => 18.
     */
    public function filter(array $filters): Collection
    {
        $query = $this->newQuery();

        foreach ($filters as $key => $value) {
            if (str_contains((string) $key, ':')) {
                [$column, $operator] = explode(':', $key, 2);
                $sqlOperator = match ($operator) {
                    'gte'  => '>=',
                    'lte'  => '<=',
                    'gt'   => '>',
                    'lt'   => '<',
                    'ne'   => '!=',
                    'like' => 'LIKE',
                    default => '=',
                };
                $queryValue = $operator === 'like' ? '%' . $value . '%' : $value;
                $query->where($column, $sqlOperator, $queryValue);
            } elseif (is_array($value)) {
                $query->whereIn((string) $key, $value);
            } else {
                $query->where((string) $key, $value);
            }
        }

        return $query->get();
    }

    /**
     * Apply dynamic ordering.
     */
    public function sort(string $column, string $direction = 'asc'): Collection
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $this->newQuery()->orderBy($column, $direction)->get();
    }

    /**
     * Return a paginator when `per_page` is in the request, otherwise all rows.
     */
    public function paginateConditional(Builder $query, Request $request): Collection|LengthAwarePaginator
    {
        if ($request->has('per_page')) {
            $perPage = (int) $request->input('per_page', 15);

            return $query->paginate(max(1, $perPage));
        }

        return $query->get();
    }

    /**
     * Specify eager-loaded relationships for subsequent queries (fluent interface).
     */
    public function withRelations(array $relations): static
    {
        $this->eagerLoads = array_merge($this->eagerLoads, $relations);

        return $this;
    }

    /**
     * Make an authenticated HTTP call to another microservice and return decoded JSON.
     */
    public function crossServiceFetch(string $serviceUrl, string $endpoint, array $params = []): mixed
    {
        $url = rtrim($serviceUrl, '/') . '/' . ltrim($endpoint, '/');

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'X-Service-Key' => config('services.internal_key', ''),
            ])
                ->timeout(10)
                ->retry(2, 200)
                ->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('CrossServiceFetch non-success response', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('CrossServiceFetch error', [
                'url'       => $url,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
