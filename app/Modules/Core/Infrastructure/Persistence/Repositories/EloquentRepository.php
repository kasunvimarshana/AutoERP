<?php

namespace Modules\Core\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Model;

class EloquentRepository extends BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->provider = $model->newQuery();
    }

    /**
     * {@inheritdoc}
     */
    protected function applyCriteria(): void
    {
        $this->resetProvider();

        // Apply eager loading
        if (!empty($this->with)) {
            $this->provider->with($this->with);
        }

        // Apply where clauses
        foreach ($this->wheres as $where) {
            $this->provider->where($where['column'], $where['operator'], $where['value'], $where['boolean']);
        }

        // Apply whereIn
        foreach ($this->whereIns as $whereIn) {
            $this->provider->whereIn($whereIn['column'], $whereIn['values'], $whereIn['boolean'], $whereIn['not']);
        }

        // Apply whereBetween
        foreach ($this->whereBetweens as $whereBetween) {
            $this->provider->whereBetween($whereBetween['column'], $whereBetween['values'], $whereBetween['boolean'], $whereBetween['not']);
        }

        // Apply whereNull
        foreach ($this->whereNulls as $whereNull) {
            $method = $whereNull['not'] ? 'whereNotNull' : 'whereNull';
            $this->provider->{$method}($whereNull['column'], $whereNull['boolean']);
        }

        // Apply orderBy
        foreach ($this->orders as $order) {
            $this->provider->orderBy($order['column'], $order['direction']);
        }

        // Apply orderByRaw
        foreach ($this->orderByRaw as $rawOrder) {
            $this->provider->orderByRaw($rawOrder['sql'], $rawOrder['bindings']);
        }

        // Apply limit and offset
        if ($this->limit !== null) {
            $this->provider->limit($this->limit);
        }
        if ($this->offset !== null) {
            $this->provider->offset($this->offset);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function resetProvider(): void
    {
        $this->provider = $this->model->newQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
        $record = $this->find($id);
        if ($record) {
            $record->update($data);
            return $record->fresh();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id): bool
    {
        $record = $this->find($id);
        if ($record) {
            return (bool) $record->delete();
        }
        return false;
    }
}
