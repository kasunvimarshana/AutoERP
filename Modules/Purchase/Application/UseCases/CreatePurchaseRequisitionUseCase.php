<?php

namespace Modules\Purchase\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Purchase\Domain\Contracts\PurchaseRequisitionRepositoryInterface;
use Modules\Purchase\Domain\Events\PurchaseRequisitionCreated;

class CreatePurchaseRequisitionUseCase
{
    public function __construct(private PurchaseRequisitionRepositoryInterface $repo) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId   = auth()->user()?->tenant_id ?? $data['tenant_id'] ?? null;
            $totalAmount = '0.00000000';
            $lines       = $data['lines'] ?? [];

            foreach ($lines as &$line) {
                $lineTotal         = bcmul((string) $line['qty'], (string) $line['unit_price'], 8);
                $line['line_total'] = $lineTotal;
                $totalAmount        = bcadd($totalAmount, $lineTotal, 8);
            }
            unset($line);

            $requisition = $this->repo->create(array_merge($data, [
                'tenant_id'    => $tenantId,
                'number'       => $this->repo->nextNumber($tenantId),
                'lines'        => $lines,
                'total_amount' => $totalAmount,
                'status'       => 'draft',
                'requested_by' => auth()->id() ?? $data['requested_by'] ?? null,
            ]));

            Event::dispatch(new PurchaseRequisitionCreated($requisition->id));

            return $requisition;
        });
    }
}
