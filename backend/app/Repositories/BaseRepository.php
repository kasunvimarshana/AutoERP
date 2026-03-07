<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Container\Container as App;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;
    protected App $app;
    protected array $eagerLoad = [];

    /** Columns used for full-text search if none specified. */
    protected array $searchableColumns = [];

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->makeModel();
    }

    /**
     * Return the fully-qualified model class name.
     */
    abstract protected function model(): string;

    /**
     * Instantiate and bind the Eloquent model.
     */
    protected function makeModel(): void
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new \RuntimeException(
                "Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model"
            );
        }

        $this->model = $model;
    }

    // ─── Query helpers ────────────────────────────────────────────────────────

    public function newQuery(): Builder
    {
        $query = $this->model->newQuery();

        if (!empty($this->eagerLoad)) {
            $query->with($this->eagerLoad);
            $this->eagerLoad = [];
        }

        return $query;
    }

    public function with(array $relations): static
    {
        $this->eagerLoad = $relations;
        return $this;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    // ─── Basic CRUD ───────────────────────────────────────────────────────────

    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->newQuery()->with($relations)->get($columns);
    }

    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->newQuery()->with($relations)->select($columns)->find($id);
    }

    public function findBy(string $field, mixed $value, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->newQuery()->with($relations)->select($columns)->where($field, $value)->first();
    }

    public function findAllBy(string $field, mixed $value, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->newQuery()->with($relations)->select($columns)->where($field, $value)->get();
    }

    public function findWhere(array $conditions, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->newQuery()->with($relations)->select($columns)->where($conditions)->get();
    }

    public function firstWhere(array $conditions, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->newQuery()->with($relations)->select($columns)->where($conditions)->first();
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data): Model
    {
        $record = $this->find($id);

        if (!$record) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Record with ID {$id} not found in " . get_class($this->model)
            );
        }

        $record->update($data);
        return $record->fresh();
    }

    public function updateWhere(array $conditions, array $data): int
    {
        return $this->newQuery()->where($conditions)->update($data);
    }

    public function delete(int $id): bool
    {
        $record = $this->find($id);

        if (!$record) {
            return false;
        }

        return (bool) $record->delete();
    }

    public function deleteWhere(array $conditions): int
    {
        return $this->newQuery()->where($conditions)->delete();
    }

    public function softDelete(int $id): bool
    {
        return $this->delete($id);
    }

    public function restore(int $id): bool
    {
        $record = $this->model->newQuery()->withTrashed()->find($id);

        if (!$record) {
            return false;
        }

        return (bool) $record->restore();
    }

    // ─── Aggregates ───────────────────────────────────────────────────────────

    public function count(array $conditions = []): int
    {
        $query = $this->newQuery();

        if (!empty($conditions)) {
            $query->where($conditions);
        }

        return $query->count();
    }

    public function exists(array $conditions): bool
    {
        return $this->newQuery()->where($conditions)->exists();
    }

    // ─── Pagination / Search / Filter / Sort ──────────────────────────────────

    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        array $relations = [],
        array $filters = [],
        string $sortBy = 'id',
        string $sortDirection = 'asc',
        string $search = ''
    ): LengthAwarePaginator {
        $query = $this->newQuery()->with($relations)->select($columns);

        $query = $this->applyFilters($query, $filters);

        if ($search !== '') {
            $searchColumns = $this->searchableColumns ?: $this->resolveSearchableColumns();
            $query = $this->applySearch($query, $search, $searchColumns);
        }

        $query = $this->applySort($query, $sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if (is_null($value)) {
                continue;
            }

            if (is_array($value)) {
                // Support range filters: ['field' => ['from' => x, 'to' => y]]
                if (isset($value['from']) || isset($value['to'])) {
                    if (isset($value['from'])) {
                        $query->where($field, '>=', $value['from']);
                    }
                    if (isset($value['to'])) {
                        $query->where($field, '<=', $value['to']);
                    }
                } else {
                    $query->whereIn($field, $value);
                }
            } elseif (str_contains((string) $field, ':')) {
                // Support operator prefix: 'amount:>=' => 100
                [$col, $op] = explode(':', $field, 2);
                $query->where($col, $op, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    public function applySearch(Builder $query, string $search, array $searchableColumns): Builder
    {
        if (empty($searchableColumns) || $search === '') {
            return $query;
        }

        $query->where(function (Builder $q) use ($search, $searchableColumns) {
            foreach ($searchableColumns as $column) {
                if (str_contains($column, '.')) {
                    // Relation column e.g. "user.name"
                    [$relation, $col] = explode('.', $column, 2);
                    $q->orWhereHas($relation, function (Builder $r) use ($col, $search) {
                        $r->where($col, 'LIKE', "%{$search}%");
                    });
                } else {
                    $q->orWhere($column, 'LIKE', "%{$search}%");
                }
            }
        });

        return $query;
    }

    public function applySort(Builder $query, string $sortBy, string $sortDirection): Builder
    {
        $direction = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';

        if (str_contains($sortBy, '.')) {
            // Relation sort: "user.name" — join and order
            [$relation, $column] = explode('.', $sortBy, 2);
            $relatedTable = $this->model->$relation()->getRelated()->getTable();
            $foreignKey   = $this->model->$relation()->getForeignKeyName();
            $ownerKey     = $this->model->$relation()->getOwnerKeyName();

            $query->join($relatedTable, "{$relatedTable}.{$ownerKey}", '=', "{$this->model->getTable()}.{$foreignKey}")
                  ->orderBy("{$relatedTable}.{$column}", $direction)
                  ->select("{$this->model->getTable()}.*");
        } else {
            $query->orderBy($sortBy, $direction);
        }

        return $query;
    }

    // ─── Bulk operations ─────────────────────────────────────────────────────

    public function bulkCreate(array $records): bool
    {
        return $this->model->newQuery()->insert($records);
    }

    public function bulkUpdate(array $records, string $uniqueBy = 'id'): int
    {
        if (empty($records)) {
            return 0;
        }

        $columns = array_keys($records[0]);
        $updated = 0;

        foreach ($records as $record) {
            $updated += $this->newQuery()
                ->where($uniqueBy, $record[$uniqueBy])
                ->update(array_diff_key($record, [$uniqueBy => null]));
        }

        return $updated;
    }

    // ─── Transactions ────────────────────────────────────────────────────────

    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    public function commit(): void
    {
        DB::commit();
    }

    public function rollback(): void
    {
        DB::rollBack();
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    // ─── Cross-service lookup ────────────────────────────────────────────────

    public function findAcrossServices(string $service, int $id): mixed
    {
        $repository = $this->app->make($service);

        if (!$repository instanceof BaseRepositoryInterface) {
            throw new \InvalidArgumentException(
                "Service [{$service}] must implement BaseRepositoryInterface."
            );
        }

        return $repository->find($id);
    }

    // ─── Internals ───────────────────────────────────────────────────────────

    /**
     * Automatically detect searchable string columns from the model's fillable list.
     */
    protected function resolveSearchableColumns(): array
    {
        return array_filter(
            $this->model->getFillable(),
            fn($col) => !in_array($col, ['password', 'remember_token', 'tenant_id', 'id'])
        );
    }
}
