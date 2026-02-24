<?php
namespace Modules\Purchase\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Purchase\Domain\Contracts\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Domain\Contracts\GoodsReceiptRepositoryInterface;
use Modules\Purchase\Domain\Events\GoodsReceived;
class ReceiveGoodsUseCase
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $poRepo,
        private GoodsReceiptRepositoryInterface $grnRepo,
    ) {}
    public function execute(string $poId, array $data): object
    {
        return DB::transaction(function () use ($poId, $data) {
            $po = $this->poRepo->findById($poId);
            if (!$po) throw new \RuntimeException('Purchase order not found.');
            if (!in_array($po->status, ['approved', 'sent', 'partially_received'])) {
                throw new \RuntimeException('Purchase order cannot receive goods in status: '.$po->status);
            }
            $grn = $this->grnRepo->create(array_merge($data, [
                'purchase_order_id' => $poId,
                'tenant_id' => $po->tenant_id,
                'received_at' => $data['received_at'] ?? now(),
                'received_by' => $data['received_by'] ?? auth()->id(),
            ]));
            $this->poRepo->update($poId, ['status' => 'partially_received']);
            // Build a price lookup keyed by product_id from PO lines (for vendor bill creation).
            $poLinesMap = collect($po->lines ?? [])
                ->keyBy(fn ($l) => $l->product_id ?? '');
            $grnLines = collect($grn->lines ?? [])
                ->map(function ($l) use ($poLinesMap) {
                    $poLine = $poLinesMap->get($l->product_id ?? '') ?? null;
                    return [
                        'product_id'   => $l->product_id ?? null,
                        'qty_accepted' => (string) ($l->qty_accepted ?? '0'),
                        'location_id'  => $l->location_id ?? null,
                        'unit_price'   => $poLine ? (string) ($poLine->unit_price ?? null) : null,
                        'tax_rate'     => $poLine ? (string) ($poLine->tax_rate ?? '0') : '0',
                        'description'  => $poLine ? (string) ($poLine->description ?? '') : '',
                    ];
                })->all();
            Event::dispatch(new GoodsReceived($poId, $grn->id, $po->tenant_id, $grnLines, $po->vendor_id ?? null));
            return $grn;
        });
    }
}
