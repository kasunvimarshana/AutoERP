<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ServiceCenter\Application\Contracts\CreateServiceOrderServiceInterface;
use Modules\ServiceCenter\Application\DTOs\CreateServiceOrderDTO;
use Modules\ServiceCenter\Domain\Entities\ServiceOrder;
use Modules\ServiceCenter\Domain\Entities\ServiceTask;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceTaskRepositoryInterface;

class CreateServiceOrderService implements CreateServiceOrderServiceInterface
{
    public function __construct(
        private readonly ServiceOrderRepositoryInterface $orders,
        private readonly ServiceTaskRepositoryInterface $tasks,
    ) {
    }

    public function execute(CreateServiceOrderDTO $dto): ServiceOrder
    {
        $order = new ServiceOrder(
            id: (string) Str::uuid(),
            tenantId: $dto->tenantId,
            assetId: $dto->assetId,
            assignedTechnicianId: $dto->assignedTechnicianId,
            orderNumber: $this->generateOrderNumber(),
            serviceType: $dto->serviceType,
            status: 'draft',
            description: $dto->description,
            scheduledAt: $dto->scheduledAt,
            startedAt: null,
            completedAt: null,
            estimatedCost: $dto->estimatedCost,
            totalCost: '0.000000',
        );

        DB::transaction(function () use ($order, $dto): void {
            $this->orders->create($order);

            foreach ($dto->tasks as $taskData) {
                $task = new ServiceTask(
                    id: (string) Str::uuid(),
                    serviceOrderId: $order->getId(),
                    taskName: $taskData['task_name'],
                    description: $taskData['description'] ?? null,
                    status: 'pending',
                    laborCost: $taskData['labor_cost'],
                    laborMinutes: $taskData['labor_minutes'] ?? null,
                );
                $this->tasks->create($task);
            }
        });

        return $order;
    }

    private function generateOrderNumber(): string
    {
        return sprintf('SVC-%s-%s', strtoupper(Str::random(6)), (new \DateTimeImmutable())->format('YmdHis'));
    }
}
