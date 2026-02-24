<?php

namespace Modules\FieldService\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\FieldService\Domain\Contracts\ServiceOrderRepositoryInterface;
use Modules\FieldService\Domain\Events\ServiceOrderAssigned;

class AssignTechnicianUseCase
{
    public function __construct(
        private ServiceOrderRepositoryInterface $orderRepo,
    ) {}

    public function execute(string $orderId, string $technicianId): object
    {
        return DB::transaction(function () use ($orderId, $technicianId) {
            $order = $this->orderRepo->findById($orderId);

            if (! $order) {
                throw new DomainException('Service order not found.');
            }

            if (! in_array($order->status, ['new', 'assigned'], true)) {
                throw new DomainException('Technician can only be assigned to new or assigned orders.');
            }

            $updated = $this->orderRepo->update($orderId, [
                'technician_id' => $technicianId,
                'status'        => 'assigned',
            ]);

            Event::dispatch(new ServiceOrderAssigned($orderId, $order->tenant_id, $technicianId));

            return $updated;
        });
    }
}
