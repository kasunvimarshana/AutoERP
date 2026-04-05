<?php
declare(strict_types=1);
namespace Modules\Maintenance\Application\Services;

use Modules\Maintenance\Domain\Entities\ServiceOrder;
use Modules\Maintenance\Domain\Exceptions\ServiceOrderNotFoundException;
use Modules\Maintenance\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;

class ServiceOrderService
{
    public function __construct(private readonly ServiceOrderRepositoryInterface $repository) {}

    public function findById(int $id): ServiceOrder
    {
        $order = $this->repository->findById($id);
        if ($order === null) throw new ServiceOrderNotFoundException($id);
        return $order;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        return $this->repository->findAllByTenant($tenantId, $filters);
    }

    public function create(array $data): ServiceOrder
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ServiceOrder
    {
        $this->findById($id);
        return $this->repository->update($id, $data) ?? $this->findById($id);
    }

    public function start(int $id): ServiceOrder
    {
        $order = $this->findById($id);
        $order->start();
        return $this->repository->update($id, [
            'status'     => ServiceOrder::STATUS_IN_PROGRESS,
            'started_at' => new \DateTimeImmutable(),
        ]) ?? $order;
    }

    public function complete(int $id, float $actualHours, float $laborCost, float $partsCost): ServiceOrder
    {
        $order = $this->findById($id);
        $order->complete($actualHours, $laborCost, $partsCost);
        return $this->repository->update($id, [
            'status'       => ServiceOrder::STATUS_COMPLETED,
            'actual_hours' => $actualHours,
            'labor_cost'   => $laborCost,
            'parts_cost'   => $partsCost,
            'completed_at' => new \DateTimeImmutable(),
        ]) ?? $order;
    }

    public function cancel(int $id): ServiceOrder
    {
        $order = $this->findById($id);
        $order->cancel();
        return $this->repository->update($id, ['status' => ServiceOrder::STATUS_CANCELLED]) ?? $order;
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
