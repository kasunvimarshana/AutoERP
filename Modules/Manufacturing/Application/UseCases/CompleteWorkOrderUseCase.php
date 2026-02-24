<?php

namespace Modules\Manufacturing\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Manufacturing\Domain\Contracts\BomRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\WorkOrderRepositoryInterface;
use Modules\Manufacturing\Domain\Events\WorkOrderCompleted;

class CompleteWorkOrderUseCase
{
    /**
     * @param  WorkOrderRepositoryInterface  $workOrderRepo
     * @param  BomRepositoryInterface|null   $bomRepo  Optional; when provided, the BOM's product
     *                                                  is included in the event so the Inventory
     *                                                  module can receive finished goods stock.
     *                                                  Null is fully backwards-compatible.
     */
    public function __construct(
        private WorkOrderRepositoryInterface $workOrderRepo,
        private ?BomRepositoryInterface      $bomRepo = null,
    ) {}

    public function execute(string $id, array $data): object
    {
        return DB::transaction(function () use ($id, $data) {
            $workOrder = $this->workOrderRepo->findById($id);

            if (! $workOrder) {
                throw new DomainException('Work order not found.');
            }

            if ($workOrder->status !== 'in_progress') {
                throw new DomainException(
                    "Work order cannot be completed from status '{$workOrder->status}'."
                );
            }

            $updated = $this->workOrderRepo->update($id, [
                'status'            => 'done',
                'actual_end'        => now(),
                'quantity_produced' => (string) $data['quantity_produced'],
            ]);

            // Optional: source/target warehouse location supplied by the caller
            $locationId         = $data['location_id'] ?? null;
            $finishedLocationId = $data['finished_location_id'] ?? $locationId;

            // Build component lines consumed during production (from work order lines)
            $components = collect($updated->lines ?? [])
                ->map(fn ($l) => [
                    'product_id'   => $l->component_product_id ?? null,
                    'qty_consumed' => (string) ($l->quantity_consumed ?? $l->quantity_required ?? '0'),
                    'location_id'  => $locationId,
                ])
                ->filter(fn ($c) => $c['product_id'] !== null)
                ->values()
                ->all();

            // Resolve finished-goods product from the associated BOM (if repo provided)
            $finishedProductId = null;
            if ($this->bomRepo !== null && ! empty($updated->bom_id)) {
                $bom               = $this->bomRepo->findById($updated->bom_id);
                $finishedProductId = $bom?->product_id;
            }

            Event::dispatch(new WorkOrderCompleted(
                $updated->id,
                $updated->tenant_id,
                (string) $data['quantity_produced'],
                $finishedProductId,
                $finishedLocationId,
                $components,
            ));

            return $updated;
        });
    }
}
