<?php
namespace Modules\Shared\Infrastructure\Repositories;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Domain\Contracts\RepositoryInterface;
abstract class BaseEloquentRepository implements RepositoryInterface
{
    public function __construct(protected Model $model) {}
    public function findById(string $id): ?object
    {
        return $this->model->newQuery()->find($id);
    }
    public function findAll(array $filters = [], int $perPage = 15): object
    {
        $query = $this->model->newQuery();
        foreach ($filters as $key => $value) {
            if ($value !== null) {
                $query->where($key, $value);
            }
        }
        return $query->paginate($perPage);
    }
    public function create(array $data): object
    {
        return $this->model->newQuery()->create($data);
    }
    public function update(string $id, array $data): object
    {
        $record = $this->findById($id);
        if (! $record) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Record [{$id}] not found.");
        }
        $record->update($data);
        return $record->fresh();
    }
    public function delete(string $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }
}
