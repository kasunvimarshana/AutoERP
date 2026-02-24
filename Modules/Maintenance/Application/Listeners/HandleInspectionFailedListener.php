<?php

namespace Modules\Maintenance\Application\Listeners;

use Modules\Maintenance\Application\UseCases\CreateMaintenanceRequestUseCase;
use Modules\QualityControl\Domain\Events\InspectionFailed;


class HandleInspectionFailedListener
{
    public function __construct(
        private CreateMaintenanceRequestUseCase $createMaintenanceRequest,
    ) {}

    public function handle(InspectionFailed $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        // Only create a maintenance request when the failed inspection is
        // linked to a specific piece of equipment.
        if ($event->equipmentId === '') {
            return;
        }

        $description = 'Quality inspection failure'
            . ($event->title !== '' ? ': ' . $event->title : '')
            . ($event->productId !== '' ? ' (product ' . $event->productId . ')' : '');

        try {
            $this->createMaintenanceRequest->execute([
                'tenant_id'    => $event->tenantId,
                'equipment_id' => $event->equipmentId,
                'requested_by' => 'system',
                'description'  => $description,
                'priority'     => $event->priority !== '' ? $event->priority : 'medium',
            ]);
        } catch (\Throwable) {
            // Graceful degradation: a maintenance request creation failure must
            // never prevent the inspection from being marked as failed.
        }
    }
}
