<?php

namespace Modules\Maintenance\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Maintenance\Domain\Contracts\EquipmentRepositoryInterface;
use Modules\Maintenance\Domain\Events\EquipmentDecommissioned;

class DecommissionEquipmentUseCase
{
    public function __construct(
        private EquipmentRepositoryInterface $equipmentRepo,
    ) {}

    public function execute(string $equipmentId): object
    {
        return DB::transaction(function () use ($equipmentId) {
            $equipment = $this->equipmentRepo->findById($equipmentId);

            if (! $equipment) {
                throw new DomainException('Equipment not found.');
            }

            if ($equipment->status === 'decommissioned') {
                throw new DomainException('Equipment is already decommissioned.');
            }

            $updated = $this->equipmentRepo->update($equipmentId, [
                'status' => 'decommissioned',
            ]);

            Event::dispatch(new EquipmentDecommissioned(
                $updated->id,
                $updated->tenant_id,
            ));

            return $updated;
        });
    }
}
