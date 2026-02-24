<?php

namespace Modules\Inventory\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Inventory\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Inventory\Domain\Events\CycleCountCreated;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

class CreateCycleCountUseCase implements UseCaseInterface
{
    public function __construct(
        private CycleCountRepositoryInterface $cycleCountRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $warehouseId = $data['warehouse_id'] ?? '';
        $tenantId    = $data['tenant_id'];
        $countDate   = $data['count_date'] ?? date('Y-m-d');

        if (empty($warehouseId)) {
            throw new DomainException('Warehouse is required for a cycle count.');
        }

        $year      = date('Y', strtotime($countDate));
        $sequence  = strtoupper(substr((string) Str::uuid(), 0, 6));
        $reference = "CC-{$year}-{$sequence}";

        return DB::transaction(function () use ($data, $tenantId, $warehouseId, $countDate, $reference) {
            $cycleCount = $this->cycleCountRepo->create([
                'id'           => (string) Str::uuid(),
                'tenant_id'    => $tenantId,
                'warehouse_id' => $warehouseId,
                'location_id'  => $data['location_id'] ?? null,
                'reference'    => $reference,
                'count_date'   => $countDate,
                'status'       => 'draft',
                'notes'        => $data['notes'] ?? null,
            ]);

            Event::dispatch(new CycleCountCreated(
                cycleCountId: $cycleCount->id,
                tenantId:     $tenantId,
                warehouseId:  $warehouseId,
                reference:    $reference,
                countDate:    $countDate,
            ));

            return $cycleCount;
        });
    }
}
