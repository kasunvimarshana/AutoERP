<?php

namespace Modules\Maintenance\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Maintenance\Domain\Contracts\EquipmentRepositoryInterface;
use Modules\Maintenance\Domain\Contracts\MaintenanceOrderRepositoryInterface;
use Modules\Maintenance\Domain\Events\MaintenanceOrderCompleted;

class CompleteMaintenanceOrderUseCase
{
    public function __construct(
        private EquipmentRepositoryInterface      $equipmentRepo,
        private MaintenanceOrderRepositoryInterface $orderRepo,
    ) {}

    public function execute(string $orderId, array $data = []): object
    {
        return DB::transaction(function () use ($orderId, $data) {
            $order = $this->orderRepo->findById($orderId);

            if (! $order) {
                throw new DomainException('Maintenance order not found.');
            }

            if ($order->status !== 'in_progress') {
                throw new DomainException('Only in-progress maintenance orders can be completed.');
            }

            $laborCost = bcadd($data['labor_cost'] ?? $order->labor_cost ?? '0', '0', 8);
            $partsCost = bcadd($data['parts_cost'] ?? $order->parts_cost ?? '0', '0', 8);

            $updated = $this->orderRepo->update($orderId, [
                'status'       => 'done',
                'completed_at' => now()->toDateTimeString(),
                'labor_cost'   => $laborCost,
                'parts_cost'   => $partsCost,
                'notes'        => $data['notes'] ?? $order->notes ?? null,
            ]);

            // Restore equipment to active status
            $this->equipmentRepo->update($order->equipment_id, [
                'status' => 'active',
            ]);

            Event::dispatch(new MaintenanceOrderCompleted(
                $updated->id,
                $updated->tenant_id,
                $updated->equipment_id,
                $laborCost,
                $partsCost,
            ));

            return $updated;
        });
    }
}
