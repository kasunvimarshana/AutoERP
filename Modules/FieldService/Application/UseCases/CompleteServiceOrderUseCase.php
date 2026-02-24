<?php

namespace Modules\FieldService\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\FieldService\Domain\Contracts\ServiceOrderRepositoryInterface;
use Modules\FieldService\Domain\Events\ServiceOrderCompleted;

class CompleteServiceOrderUseCase
{
    public function __construct(
        private ServiceOrderRepositoryInterface $orderRepo,
    ) {}

    public function execute(string $orderId, array $data = []): object
    {
        return DB::transaction(function () use ($orderId, $data) {
            $order = $this->orderRepo->findById($orderId);

            if (! $order) {
                throw new DomainException('Service order not found.');
            }

            if (! in_array($order->status, ['assigned', 'in_progress'], true)) {
                throw new DomainException('Only assigned or in-progress orders can be completed.');
            }

            $updated = $this->orderRepo->update($orderId, [
                'status'           => 'done',
                'duration_hours'   => $data['duration_hours'] ?? $order->duration_hours,
                'labor_cost'       => $data['labor_cost'] ?? $order->labor_cost,
                'parts_cost'       => $data['parts_cost'] ?? $order->parts_cost,
                'resolution_notes' => $data['resolution_notes'] ?? $order->resolution_notes,
                'completed_at'     => now(),
            ]);

            Event::dispatch(new ServiceOrderCompleted($orderId, $order->tenant_id));

            return $updated;
        });
    }
}
