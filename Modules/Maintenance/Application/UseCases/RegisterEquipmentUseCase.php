<?php

namespace Modules\Maintenance\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Maintenance\Domain\Contracts\EquipmentRepositoryInterface;
use Modules\Maintenance\Domain\Events\EquipmentRegistered;

class RegisterEquipmentUseCase
{
    public function __construct(
        private EquipmentRepositoryInterface $equipmentRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $equipment = $this->equipmentRepo->create([
                'tenant_id'        => $data['tenant_id'],
                'name'             => $data['name'],
                'serial_number'    => $data['serial_number'],
                'category'         => $data['category'] ?? null,
                'location'         => $data['location'] ?? null,
                'assigned_team_id' => $data['assigned_team_id'] ?? null,
                'purchase_date'    => $data['purchase_date'] ?? null,
                'status'           => 'active',
                'notes'            => $data['notes'] ?? null,
            ]);

            Event::dispatch(new EquipmentRegistered(
                $equipment->id,
                $equipment->tenant_id,
                $equipment->name,
                $equipment->serial_number,
            ));

            return $equipment;
        });
    }
}
