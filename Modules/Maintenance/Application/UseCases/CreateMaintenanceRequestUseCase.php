<?php

namespace Modules\Maintenance\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Maintenance\Domain\Contracts\EquipmentRepositoryInterface;
use Modules\Maintenance\Domain\Contracts\MaintenanceRequestRepositoryInterface;
use Modules\Maintenance\Domain\Events\MaintenanceRequestCreated;

class CreateMaintenanceRequestUseCase
{
    public function __construct(
        private EquipmentRepositoryInterface        $equipmentRepo,
        private MaintenanceRequestRepositoryInterface $requestRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $equipment = $this->equipmentRepo->findById($data['equipment_id']);

            if (! $equipment) {
                throw new DomainException('Equipment not found.');
            }

            if ($equipment->status === 'decommissioned') {
                throw new DomainException('Cannot create maintenance request for decommissioned equipment.');
            }

            $request = $this->requestRepo->create([
                'tenant_id'    => $data['tenant_id'],
                'equipment_id' => $data['equipment_id'],
                'requested_by' => $data['requested_by'],
                'description'  => $data['description'],
                'priority'     => $data['priority'] ?? 'medium',
                'status'       => 'new',
            ]);

            Event::dispatch(new MaintenanceRequestCreated(
                $request->id,
                $request->tenant_id,
                $request->equipment_id,
                $request->requested_by,
            ));

            return $request;
        });
    }
}
