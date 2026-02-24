<?php

namespace Modules\Maintenance\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Maintenance\Domain\Contracts\EquipmentRepositoryInterface;
use Modules\Maintenance\Domain\Contracts\MaintenanceOrderRepositoryInterface;

class CreateMaintenanceOrderUseCase
{
    public function __construct(
        private EquipmentRepositoryInterface      $equipmentRepo,
        private MaintenanceOrderRepositoryInterface $orderRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $equipment = $this->equipmentRepo->findById($data['equipment_id']);

            if (! $equipment) {
                throw new DomainException('Equipment not found.');
            }

            if ($equipment->status === 'decommissioned') {
                throw new DomainException('Cannot create maintenance order for decommissioned equipment.');
            }

            $reference = $this->generateReference($data['tenant_id']);

            $order = $this->orderRepo->create([
                'tenant_id'        => $data['tenant_id'],
                'reference'        => $reference,
                'equipment_id'     => $data['equipment_id'],
                'order_type'       => $data['order_type'],
                'description'      => $data['description'] ?? null,
                'scheduled_date'   => $data['scheduled_date'] ?? null,
                'assigned_to'      => $data['assigned_to'] ?? null,
                'labor_cost'       => bcadd($data['labor_cost'] ?? '0', '0', 8),
                'parts_cost'       => bcadd($data['parts_cost'] ?? '0', '0', 8),
                'status'           => 'draft',
            ]);

            return $order;
        });
    }

    private function generateReference(string $tenantId): string
    {
        $year  = now()->year;
        $count = DB::table('maintenance_orders')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count();

        $sequence = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);

        return "MO-{$year}-{$sequence}";
    }
}
