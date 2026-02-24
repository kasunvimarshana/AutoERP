<?php
namespace Modules\Sales\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Sales\Domain\Contracts\QuotationRepositoryInterface;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\Events\OrderConfirmed;
class ConvertQuotationToOrderUseCase
{
    public function __construct(
        private QuotationRepositoryInterface $quotationRepo,
        private SalesOrderRepositoryInterface $orderRepo,
    ) {}
    public function execute(string $quotationId, array $extra = []): object
    {
        return DB::transaction(function () use ($quotationId, $extra) {
            $quotation = $this->quotationRepo->findById($quotationId);
            if (!$quotation) throw new \RuntimeException('Quotation not found.');
            if (!in_array($quotation->status, ['accepted', 'sent'])) {
                throw new \RuntimeException('Only accepted or sent quotations can be converted.');
            }
            $tenantId = $quotation->tenant_id;
            $order = $this->orderRepo->create(array_merge([
                'tenant_id' => $tenantId,
                'number' => $this->orderRepo->nextNumber($tenantId),
                'customer_id' => $quotation->customer_id,
                'quotation_id' => $quotation->id,
                'status' => 'confirmed',
                'subtotal' => $quotation->subtotal,
                'tax_total' => $quotation->tax_total,
                'total' => $quotation->total,
                'currency' => $quotation->currency,
                'confirmed_at' => now(),
            ], $extra));
            $this->quotationRepo->update($quotationId, ['status' => 'accepted']);
            Event::dispatch(new OrderConfirmed($order->id));
            return $order;
        });
    }
}
