<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Shared\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * BaseRepository
 *
 * Fully dynamic, reusable, enterprise-grade repository that implements
 * all CRUD operations, dynamic pagination, filtering, searching, sorting,
 * relation loading, and cross-service data access.
 *
 * No hardcoded values — everything is parameterised.
 *
 * Usage:
 *   class UserRepository extends BaseRepository
 *   {
 *       public function __construct(User $model)
 *       {
 *           parent::__construct($model);
 *       }
 *   }
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * The underlying Eloquent model instance.
     */
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Query building helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return a fresh query builder for the model.
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Apply filters, relations, and ordering to a query builder.
     *
     * Supported filter syntax:
     *   'column'           => $value                 (WHERE column = value)
     *   'column:like'      => $value                 (WHERE column LIKE %value%)
     *   'column:not'       => $value                 (WHERE column != value)
     *   'column:in'        => [$a, $b]               (WHERE column IN (...))
     *   'column:not_in'    => [$a, $b]               (WHERE column NOT IN (...))
     *   'column:null'      => true|false             (WHERE column IS NULL / IS NOT NULL)
     *   'column:between'   => [$min, $max]           (WHERE column BETWEEN min AND max)
     *   'column:gt'        => $value                 (WHERE column > value)
     *   'column:gte'       => $value                 (WHERE column >= value)
     *   'column:lt'        => $value                 (WHERE column < value)
     *   'column:lte'       => $value                 (WHERE column <= value)
     *
     * @param  Builder               $query
     * @param  array<string, mixed>  $filters
     * @param  array<string>         $relations
     * @param  array<string, string> $orderBy   ['column' => 'asc|desc']
     * @return Builder
     */
    protected function applyQueryOptions(
        Builder $query,
        array   $filters   = [],
        array   $relations = [],
        array   $orderBy   = []
    ): Builder {
        // ── Filters ──────────────────────────────────────────────────────────
        foreach ($filters as $rawKey => $value) {
            [$column, $operator] = array_pad(explode(':', $rawKey, 2), 2, 'eq');

            match ($operator) {
                'like'     => $query->where($column, 'LIKE', "%{$value}%"),
                'not'      => $query->where($column, '!=', $value),
                'in'       => $query->whereIn($column, (array) $value),
                'not_in'   => $query->whereNotIn($column, (array) $value),
                'null'     => $value ? $query->whereNull($column) : $query->whereNotNull($column),
                'between'  => $query->whereBetween($column, $value),
                'gt'       => $query->where($column, '>', $value),
                'gte'      => $query->where($column, '>=', $value),
                'lt'       => $query->where($column, '<', $value),
                'lte'      => $query->where($column, '<=', $value),
                default    => $query->where($column, '=', $value),
            };
        }

        // ── Relations ─────────────────────────────────────────────────────────
        if (!empty($relations)) {
            $query->with($relations);
        }

        // ── Ordering ──────────────────────────────────────────────────────────
        foreach ($orderBy as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BaseRepositoryInterface implementation
    // ─────────────────────────────────────────────────────────────────────────

    /** {@inheritDoc} */
    public function all(
        array $filters   = [],
        array $columns   = ['*'],
        array $relations = [],
        array $orderBy   = []
    ): Collection {
        return $this->applyQueryOptions(
            $this->query()->select($columns),
            $filters,
            $relations,
            $orderBy
        )->get();
    }

    /** {@inheritDoc} */
    public function paginate(
        array  $filters   = [],
        int    $perPage   = 15,
        array  $columns   = ['*'],
        string $pageName  = 'page',
        ?int   $page      = null,
        array  $relations = [],
        array  $orderBy   = []
    ): LengthAwarePaginator {
        return $this->applyQueryOptions(
            $this->query()->select($columns),
            $filters,
            $relations,
            $orderBy
        )->paginate(
            perPage:  $perPage,
            columns:  ['*'],   // columns already applied via select()
            pageName: $pageName,
            page:     $page
        );
    }

    /** {@inheritDoc} */
    public function find(
        int|string $id,
        array      $columns   = ['*'],
        array      $relations = []
    ): ?Model {
        $query = $this->query()->select($columns);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /** {@inheritDoc} */
    public function findOrFail(
        int|string $id,
        array      $columns   = ['*'],
        array      $relations = []
    ): Model {
        $query = $this->query()->select($columns);

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->findOrFail($id);
    }

    /** {@inheritDoc} */
    public function findBy(
        array $criteria,
        array $columns   = ['*'],
        array $relations = []
    ): ?Model {
        return $this->applyQueryOptions(
            $this->query()->select($columns),
            $criteria,
            $relations
        )->first();
    }

    /** {@inheritDoc} */
    public function findAllBy(
        array $criteria,
        array $columns   = ['*'],
        array $relations = [],
        array $orderBy   = []
    ): Collection {
        return $this->applyQueryOptions(
            $this->query()->select($columns),
            $criteria,
            $relations,
            $orderBy
        )->get();
    }

    /** {@inheritDoc} */
    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    /** {@inheritDoc} */
    public function update(int|string $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);

        return $record->fresh() ?? $record;
    }

    /** {@inheritDoc} */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->newQuery()->updateOrCreate($attributes, $values);
    }

    /** {@inheritDoc} */
    public function delete(int|string $id): bool
    {
        $record = $this->findOrFail($id);

        return (bool) $record->delete();
    }

    /** {@inheritDoc} */
    public function softDelete(int|string $id): bool
    {
        // Guard: model must use SoftDeletes
        if (!in_array(SoftDeletes::class, class_uses_recursive($this->model), true)) {
            throw new \LogicException(
                sprintf('Model [%s] does not use the SoftDeletes trait.', get_class($this->model))
            );
        }

        $record = $this->findOrFail($id);

        return (bool) $record->delete();
    }

    /** {@inheritDoc} */
    public function restore(int|string $id): bool
    {
        if (!in_array(SoftDeletes::class, class_uses_recursive($this->model), true)) {
            throw new \LogicException(
                sprintf('Model [%s] does not use the SoftDeletes trait.', get_class($this->model))
            );
        }

        $record = $this->model->newQuery()->withTrashed()->findOrFail($id);

        return (bool) $record->restore();
    }

    /** {@inheritDoc} */
    public function count(array $criteria = []): int
    {
        return $this->applyQueryOptions($this->query(), $criteria)->count();
    }

    /** {@inheritDoc} */
    public function bulkInsert(array $data): bool
    {
        return $this->model->newQuery()->insert($data);
    }

    /** {@inheritDoc} */
    public function search(
        string $term,
        array  $columns,
        array  $filters   = [],
        int    $perPage   = 15,
        array  $relations = []
    ): LengthAwarePaginator {
        $query = $this->applyQueryOptions($this->query(), $filters, $relations);

        // Build LIKE clause across all searchable columns
        $query->where(function (Builder $q) use ($term, $columns): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $q->{$method}($column, 'LIKE', "%{$term}%");
            }
        });

        return $query->paginate($perPage);
    }

    /** {@inheritDoc} */
    public function rawQuery(string $query, array $bindings = []): Collection
    {
        $results = DB::select($query, $bindings);

        // Wrap stdClass objects in a plain Collection
        return collect($results)->map(
            fn ($row) => (object) (array) $row
        );
    }

    /** {@inheritDoc} */
    public function loadRelations(Model|Collection $resource, array $relations): Model|Collection
    {
        if ($resource instanceof Model) {
            return $resource->loadMissing($relations);
        }

        return $resource->loadMissing($relations);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Transaction helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** {@inheritDoc} */
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /** {@inheritDoc} */
    public function commit(): void
    {
        DB::commit();
    }

    /** {@inheritDoc} */
    public function rollBack(): void
    {
        DB::rollBack();
    }
}
