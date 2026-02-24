<?php

namespace Modules\Accounting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\InvoiceRepositoryInterface;
use Modules\Accounting\Domain\Events\InvoiceCreated;

class CreateInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = auth()->user()?->tenant_id ?? $data['tenant_id'] ?? null;

            $subtotal = '0.00000000';
            $taxTotal = '0.00000000';

            foreach ($data['lines'] ?? [] as $line) {
                $lineTotal = bcmul((string) $line['quantity'], (string) $line['unit_price'], 8);
                $subtotal  = bcadd($subtotal, $lineTotal, 8);
                $lineTax   = bcmul($lineTotal, (string) ($line['tax_rate'] ?? '0'), 8);
                $taxTotal  = bcadd($taxTotal, $lineTax, 8);
            }

            $total = bcadd($subtotal, $taxTotal, 8);

            $invoice = $this->repo->create(array_merge($data, [
                'tenant_id'   => $tenantId,
                'number'      => $this->repo->nextNumber($tenantId),
                'status'      => 'draft',
                'subtotal'    => $subtotal,
                'tax_total'   => $taxTotal,
                'total'       => $total,
                'amount_paid' => '0.00000000',
                'amount_due'  => $total,
                'created_by'  => auth()->id(),
            ]));

            Event::dispatch(new InvoiceCreated($invoice->id));

            return $invoice;
        });
    }
}
