<?php
namespace Modules\Purchase\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Purchase\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Domain\Events\PurchaseOrderCreated;
class CreatePurchaseOrderUseCase
{
    public function __construct(private PurchaseOrderRepositoryInterface $repo) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = auth()->user()?->tenant_id ?? $data['tenant_id'] ?? null;
            $subtotal = '0.00000000';
            $taxTotal = '0.00000000';
            $lines = $data['lines'] ?? [];
            foreach ($lines as &$line) {
                $lineTotal = bcmul((string)$line['qty'], (string)$line['unit_price'], 8);
                $taxRate = isset($line['tax_rate']) ? (string)$line['tax_rate'] : '0';
                $tax = bcmul($lineTotal, bcdiv($taxRate, '100', 8), 8);
                $line['line_total'] = $lineTotal;
                $line['tax_amount'] = $tax;
                $subtotal = bcadd($subtotal, $lineTotal, 8);
                $taxTotal = bcadd($taxTotal, $tax, 8);
            }
            unset($line);
            $po = $this->repo->create(array_merge($data, [
                'number' => $this->repo->nextNumber($tenantId),
                'lines' => $lines,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => bcadd($subtotal, $taxTotal, 8),
                'status' => 'draft',
            ]));
            Event::dispatch(new PurchaseOrderCreated($po->id));
            return $po;
        });
    }
}
