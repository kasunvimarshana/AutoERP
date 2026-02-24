<?php

namespace Modules\Purchase\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Purchase\Domain\Contracts\PurchaseRequisitionRepositoryInterface;
use Modules\Purchase\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Domain\Events\PurchaseOrderCreated;

class ConvertRequisitionToPurchaseOrderUseCase
{
    public function __construct(
        private PurchaseRequisitionRepositoryInterface $requisitionRepo,
        private PurchaseOrderRepositoryInterface       $poRepo,
    ) {}

    public function execute(string $requisitionId, array $poData): object
    {
        return DB::transaction(function () use ($requisitionId, $poData) {
            $requisition = $this->requisitionRepo->findById($requisitionId);

            if (! $requisition) {
                throw new \RuntimeException('Purchase requisition not found.');
            }

            if ($requisition->status !== 'approved') {
                throw new \RuntimeException('Only approved requisitions can be converted to a purchase order.');
            }

            $tenantId    = $requisition->tenant_id;
            $subtotal    = '0.00000000';
            $taxTotal    = '0.00000000';
            $lines       = $poData['lines'] ?? [];

            foreach ($lines as &$line) {
                $lineTotal         = bcmul((string) $line['qty'], (string) $line['unit_price'], 8);
                $taxRate           = isset($line['tax_rate']) ? (string) $line['tax_rate'] : '0';
                $tax               = bcmul($lineTotal, bcdiv($taxRate, '100', 8), 8);
                $line['line_total'] = $lineTotal;
                $line['tax_amount'] = $tax;
                $subtotal           = bcadd($subtotal, $lineTotal, 8);
                $taxTotal           = bcadd($taxTotal, $tax, 8);
            }
            unset($line);

            $po = $this->poRepo->create(array_merge($poData, [
                'tenant_id'      => $tenantId,
                'number'         => $this->poRepo->nextNumber($tenantId),
                'lines'          => $lines,
                'subtotal'       => $subtotal,
                'tax_total'      => $taxTotal,
                'total'          => bcadd($subtotal, $taxTotal, 8),
                'status'         => 'draft',
                'requisition_id' => $requisitionId,
            ]));

            $this->requisitionRepo->update($requisitionId, ['status' => 'po_raised']);

            Event::dispatch(new PurchaseOrderCreated($po->id));

            return $po;
        });
    }
}
