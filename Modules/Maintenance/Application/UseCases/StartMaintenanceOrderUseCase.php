<?php

namespace Modules\Maintenance\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Maintenance\Domain\Contracts\EquipmentRepositoryInterface;
use Modules\Maintenance\Domain\Contracts\MaintenanceOrderRepositoryInterface;
use Modules\Maintenance\Domain\Events\MaintenanceOrderStarted;

class StartMaintenanceOrderUseCase
{
    public function __construct(
        private EquipmentRepositoryInterface      $equipmentRepo,
        private MaintenanceOrderRepositoryInterface $orderRepo,
    ) {}

    public function execute(string $orderId): object
    {
        return DB::transaction(function () use ($orderId) {
            $order = $this->orderRepo->findById($orderId);

            if (! $order) {
                throw new DomainException('Maintenance order not found.');
            }

            if ($order->status === 'done' || $order->status === 'cancelled') {
                throw new DomainException('Cannot start a maintenance order that is already done or cancelled.');
            }

            if ($order->status === 'in_progress') {
                throw new DomainException('Maintenance order is already in progress.');
            }

            $updated = $this->orderRepo->update($orderId, [
                'status'     => 'in_progress',
                'started_at' => now()->toDateTimeString(),
            ]);

            // Mark equipment under maintenance
            $this->equipmentRepo->update($order->equipment_id, [
                'status' => 'under_maintenance',
            ]);

            Event::dispatch(new MaintenanceOrderStarted(
                $updated->id,
                $updated->tenant_id,
                $updated->equipment_id,
                $updated->order_type,
            ));

            return $updated;
        });
    }
}
