<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\ServiceCenter\Application\Contracts\CompleteServiceOrderServiceInterface;
use Modules\ServiceCenter\Application\DTOs\CompleteServiceOrderDTO;
use Modules\ServiceCenter\Domain\Entities\ServiceOrder;
use Modules\ServiceCenter\Domain\Entities\ServicePartUsage;
use Modules\ServiceCenter\Domain\Events\ServiceOrderCompleted;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServicePartUsageRepositoryInterface;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceTaskRepositoryInterface;

class CompleteServiceOrderService implements CompleteServiceOrderServiceInterface
{
    public function __construct(
        private readonly ServiceOrderRepositoryInterface $orders,
        private readonly ServiceTaskRepositoryInterface $tasks,
        private readonly ServicePartUsageRepositoryInterface $partUsages,
    ) {
    }

    public function execute(CompleteServiceOrderDTO $dto): ServiceOrder
    {
        $order = $this->orders->findById($dto->serviceOrderId);

        if ($order === null || $order->getTenantId() !== $dto->tenantId) {
            throw new \RuntimeException('Service order not found.');
        }

        if ($order->getStatus() === 'completed' || $order->getStatus() === 'cancelled') {
            throw new \RuntimeException('Service order cannot be completed in its current status: ' . $order->getStatus());
        }

        return DB::transaction(function () use ($order, $dto): ServiceOrder {
            $partsCost = '0.000000';

            foreach ($dto->partsUsed as $partData) {
                $unitCost = $partData['unit_cost'];
                $quantity = (int) $partData['quantity'];
                $lineTotal = bcmul($unitCost, (string) $quantity, 6);
                $partsCost = bcadd($partsCost, $lineTotal, 6);

                $part = new ServicePartUsage(
                    id: (string) Str::uuid(),
                    serviceOrderId: $order->getId(),
                    inventoryItemId: $partData['inventory_item_id'] ?? null,
                    partName: $partData['part_name'],
                    partNumber: $partData['part_number'],
                    quantity: $quantity,
                    unitCost: $unitCost,
                    totalCost: $lineTotal,
                );
                $this->partUsages->create($part);
            }

            // Sum labor costs from tasks
            $laborCost = '0.000000';
            foreach ($this->tasks->getByServiceOrder($order->getId()) as $task) {
                $laborCost = bcadd($laborCost, $task->getLaborCost(), 6);
                $task->complete();
                $this->tasks->update($task);
            }

            $totalCost = bcadd($partsCost, $laborCost, 6);
            $completedAt = new \DateTime();
            $order->complete($completedAt, $totalCost);
            $this->orders->update($order);

            Event::dispatch(new ServiceOrderCompleted(
                tenantId: $order->getTenantId(),
                serviceOrderId: $order->getId(),
                orderNumber: $order->getOrderNumber(),
                assetId: $order->getAssetId(),
                totalCost: $totalCost,
                completedAt: \DateTimeImmutable::createFromMutable($completedAt),
            ));

            return $order;
        });
    }
}
