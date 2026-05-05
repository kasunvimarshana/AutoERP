<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\ServiceCenter\Domain\Entities\ServiceTask;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceTaskRepositoryInterface;
use Modules\ServiceCenter\Infrastructure\Persistence\Eloquent\Models\ServiceTaskModel;

class EloquentServiceTaskRepository implements ServiceTaskRepositoryInterface
{
    public function create(ServiceTask $task): void
    {
        ServiceTaskModel::create([
            'id' => $task->getId(),
            'service_order_id' => $task->getServiceOrderId(),
            'task_name' => $task->getTaskName(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'labor_cost' => $task->getLaborCost(),
            'labor_minutes' => $task->getLaborMinutes(),
        ]);
    }

    public function findById(string $id): ?ServiceTask
    {
        $model = ServiceTaskModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function getByServiceOrder(string $serviceOrderId): array
    {
        $models = ServiceTaskModel::where('service_order_id', $serviceOrderId)->get();
        return $models->map(fn ($m) => $this->toDomain($m))->all();
    }

    public function update(ServiceTask $task): void
    {
        ServiceTaskModel::findOrFail($task->getId())->update([
            'task_name' => $task->getTaskName(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'labor_cost' => $task->getLaborCost(),
            'labor_minutes' => $task->getLaborMinutes(),
        ]);
    }

    public function delete(string $id): void
    {
        ServiceTaskModel::findOrFail($id)->delete();
    }

    private function toDomain(ServiceTaskModel $model): ServiceTask
    {
        return new ServiceTask(
            id: (string) $model->id,
            serviceOrderId: (string) $model->service_order_id,
            taskName: (string) $model->task_name,
            description: $model->description !== null ? (string) $model->description : null,
            status: (string) $model->status,
            laborCost: (string) $model->labor_cost,
            laborMinutes: $model->labor_minutes !== null ? (int) $model->labor_minutes : null,
        );
    }
}
