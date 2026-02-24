<?php

namespace Modules\Manufacturing\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Manufacturing\Domain\Contracts\BomLineRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\BomRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\WorkOrderLineRepositoryInterface;
use Modules\Manufacturing\Domain\Contracts\WorkOrderRepositoryInterface;

class CreateWorkOrderUseCase
{
    public function __construct(
        private BomRepositoryInterface          $bomRepo,
        private BomLineRepositoryInterface      $bomLineRepo,
        private WorkOrderRepositoryInterface    $workOrderRepo,
        private WorkOrderLineRepositoryInterface $workOrderLineRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? auth()->user()?->tenant_id ?? null;

            $bom = $this->bomRepo->findById($data['bom_id']);

            if (! $bom || $bom->status !== 'active') {
                throw new DomainException('BOM not found or not in active status.');
            }

            $referenceNo = $this->generateReferenceNo($tenantId);

            $workOrder = $this->workOrderRepo->create([
                'tenant_id'        => $tenantId,
                'bom_id'           => $bom->id,
                'reference_no'     => $referenceNo,
                'quantity_planned' => $data['quantity_planned'],
                'quantity_produced' => '0.00000000',
                'status'           => 'draft',
                'scheduled_start'  => $data['scheduled_start'] ?? null,
                'scheduled_end'    => $data['scheduled_end'] ?? null,
                'actual_start'     => null,
                'actual_end'       => null,
            ]);

            $bomLines = $this->bomLineRepo->findByBom($bom->id);

            foreach ($bomLines as $bomLine) {
                // quantity_required = bom_line_qty * wo_qty_planned * (1 + scrap_rate / 100)
                $baseQty = bcmul((string) $bomLine->quantity, (string) $data['quantity_planned'], 8);
                $scrapMultiplier = bcadd('1.00000000', bcdiv((string) $bomLine->scrap_rate, '100.00000000', 8), 8);
                $quantityRequired = bcmul($baseQty, $scrapMultiplier, 8);

                $this->workOrderLineRepo->create([
                    'tenant_id'            => $tenantId,
                    'work_order_id'        => $workOrder->id,
                    'component_product_id' => $bomLine->component_product_id,
                    'component_name'       => $bomLine->component_name,
                    'quantity_required'    => $quantityRequired,
                    'quantity_consumed'    => '0.00000000',
                    'unit'                 => $bomLine->unit,
                ]);
            }

            return $workOrder;
        });
    }

    private function generateReferenceNo(string $tenantId): string
    {
        $year = now()->year;

        $count = DB::table('mfg_work_orders')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->withTrashed()
            ->count();

        $sequence = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);

        return "WO-{$year}-{$sequence}";
    }
}
