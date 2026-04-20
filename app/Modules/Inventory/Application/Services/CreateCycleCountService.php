<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Domain\Entities\CycleCountHeader;
use Modules\Inventory\Domain\Entities\CycleCountLine;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;

class CreateCycleCountService implements CreateCycleCountServiceInterface
{
    public function __construct(private readonly CycleCountRepositoryInterface $cycleCountRepository) {}

    public function execute(array $data): CycleCountHeader
    {
        $lines = [];
        foreach ($data['lines'] as $line) {
            $systemQty = $this->resolveSystemQty(
                tenantId: (int) $data['tenant_id'],
                productId: (int) $line['product_id'],
                locationId: isset($data['location_id']) ? (int) $data['location_id'] : null,
                variantId: isset($line['variant_id']) ? (int) $line['variant_id'] : null,
                batchId: isset($line['batch_id']) ? (int) $line['batch_id'] : null,
                serialId: isset($line['serial_id']) ? (int) $line['serial_id'] : null,
            );

            $countedQty = isset($line['counted_qty']) ? (string) $line['counted_qty'] : $systemQty;
            $varianceQty = bcsub($countedQty, $systemQty, 6);
            $unitCost = isset($line['unit_cost']) ? (string) $line['unit_cost'] : '0.000000';
            $varianceValue = bcmul($varianceQty, $unitCost, 6);

            $lines[] = new CycleCountLine(
                tenantId: (int) $data['tenant_id'],
                productId: (int) $line['product_id'],
                variantId: isset($line['variant_id']) ? (int) $line['variant_id'] : null,
                batchId: isset($line['batch_id']) ? (int) $line['batch_id'] : null,
                serialId: isset($line['serial_id']) ? (int) $line['serial_id'] : null,
                systemQty: $systemQty,
                countedQty: $countedQty,
                varianceQty: $varianceQty,
                unitCost: $unitCost,
                varianceValue: $varianceValue,
                adjustmentMovementId: null,
            );
        }

        $header = new CycleCountHeader(
            tenantId: (int) $data['tenant_id'],
            warehouseId: (int) $data['warehouse_id'],
            locationId: isset($data['location_id']) ? (int) $data['location_id'] : null,
            status: 'draft',
            countedByUserId: isset($data['counted_by_user_id']) ? (int) $data['counted_by_user_id'] : null,
            countedAt: null,
            approvedByUserId: null,
            approvedAt: null,
            lines: $lines,
        );

        return $this->cycleCountRepository->create($header);
    }

    private function resolveSystemQty(
        int $tenantId,
        int $productId,
        ?int $locationId,
        ?int $variantId,
        ?int $batchId,
        ?int $serialId,
    ): string {
        $query = DB::table('stock_levels')
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('batch_id', $batchId)
            ->where('serial_id', $serialId);

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        }

        $value = $query->value('quantity_on_hand');

        return $value !== null ? (string) $value : '0.000000';
    }
}
