<?php

declare(strict_types=1);

namespace Modules\Core\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Core\Application\Contracts\ServiceInterface;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

abstract class BaseService implements ServiceInterface
{
    protected RepositoryInterface $repository;

    protected array $events = [];

    /**
     * Allowed columns for sorting. Override in child classes to restrict.
     *
     * @var list<string>
     */
    protected array $allowedSortColumns = [];

    /**
     * Allowed relations for eager loading. Override in child classes to restrict.
     *
     * @var list<string>
     */
    protected array $allowedIncludes = [];

    /**
     * Allowed columns for filtering. Override in child classes to restrict.
     *
     * @var list<string>
     */
    protected array $allowedFilterFields = [];

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Execute the service with automatic transaction wrapping.
     */
    public function execute(array $data = []): mixed
    {
        return DB::transaction(function () use ($data) {
            $result = $this->handle($data);
            $this->dispatchEvents();

            return $result;
        });
    }

    /**
     * The actual business logic to be implemented by child classes.
     */
    abstract protected function handle(array $data): mixed;

    /**
     * Register domain events to be dispatched after transaction.
     */
    protected function dispatchEvents(): void
    {
        foreach ($this->events as $event) {
            Event::dispatch($event);
        }
        $this->events = [];
    }

    /**
     * Add an event to be dispatched after transaction.
     */
    protected function addEvent(object $event): void
    {
        $this->events[] = $event;
    }

    /**
     * Find a record by ID.
     */
    public function find(mixed $id): mixed
    {
        return $this->repository->find($id);
    }

    /**
     * List records with filters and pagination.
     */
    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): mixed
    {
        $perPage = $perPage ?? config('core.pagination.per_page', 15);
        $pageName = config('core.pagination.page_name', 'page');

        $repo = $this->repository->resetCriteria();

        foreach ($filters as $field => $value) {
            if ($this->allowedFilterFields !== [] && ! in_array($field, $this->allowedFilterFields, true)) {
                continue;
            }

            if (is_string($value) && str_contains($value, '%')) {
                $repo->where($field, 'like', $value);
            } else {
                $repo->where($field, $value);
            }
        }

        if ($sort !== null && $sort !== '') {
            // Support both "-column" (REST convention) and "column:direction" formats
            if (str_starts_with($sort, '-')) {
                $column = substr($sort, 1);
                $direction = 'desc';
            } else {
                $parts = explode(':', $sort, 2);
                $column = $parts[0];
                $direction = $parts[1] ?? 'asc';
            }

            if (! in_array(strtolower($direction), ['asc', 'desc'], true)) {
                $direction = 'asc';
            }

            if ($this->allowedSortColumns === [] || in_array($column, $this->allowedSortColumns, true)) {
                if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $column) === 1) {
                    $repo->orderBy($column, $direction);
                }
            }
        }

        if ($include !== null && $include !== '') {
            $relations = array_filter(array_map('trim', explode(',', $include)));
            if ($this->allowedIncludes !== []) {
                $relations = array_intersect($relations, $this->allowedIncludes);
            }
            if ($relations !== []) {
                $repo->with($relations);
            }
        }

        return $repo->paginate($perPage, ['*'], $pageName, $page);
    }

    /**
     * Update a record.
     */
    public function update(mixed $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $result = $this->handleUpdate($id, $data);
            $this->dispatchEvents();

            return $result;
        });
    }

    /**
     * Delete a record.
     */
    public function delete(mixed $id): mixed
    {
        return DB::transaction(function () use ($id) {
            $result = $this->handleDelete($id);
            $this->dispatchEvents();

            return $result;
        });
    }

    protected function handleUpdate(mixed $id, array $data): mixed
    {
        $model = $this->repository->find($id);
        if (! $model) {
            throw new NotFoundException('Record', $id);
        }
        $this->repository->update($id, $data);

        return $this->repository->find($id);
    }

    protected function handleDelete(mixed $id): bool
    {
        return $this->repository->delete($id);
    }
}
