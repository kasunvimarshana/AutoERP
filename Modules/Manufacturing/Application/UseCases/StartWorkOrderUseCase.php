<?php

namespace Modules\Manufacturing\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Manufacturing\Domain\Contracts\WorkOrderRepositoryInterface;
use Modules\Manufacturing\Domain\Events\WorkOrderStarted;

class StartWorkOrderUseCase
{
    public function __construct(
        private WorkOrderRepositoryInterface $workOrderRepo,
    ) {}

    public function execute(string $id): object
    {
        return DB::transaction(function () use ($id) {
            $workOrder = $this->workOrderRepo->findById($id);

            if (! $workOrder) {
                throw new DomainException('Work order not found.');
            }

            if (! in_array($workOrder->status, ['draft', 'confirmed'], true)) {
                throw new DomainException(
                    "Work order cannot be started from status '{$workOrder->status}'."
                );
            }

            $updated = $this->workOrderRepo->update($id, [
                'status'       => 'in_progress',
                'actual_start' => now(),
            ]);

            Event::dispatch(new WorkOrderStarted($updated->id, $updated->tenant_id));

            return $updated;
        });
    }
}
