<?php
namespace Modules\Sales\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\Events\OrderConfirmed;
class ConfirmOrderUseCase
{
    public function __construct(private SalesOrderRepositoryInterface $repo) {}
    public function execute(string $orderId): object
    {
        return DB::transaction(function () use ($orderId) {
            $order = $this->repo->findById($orderId);
            if (!$order) throw new \RuntimeException('Order not found.');
            if ($order->status !== 'draft') throw new \RuntimeException('Only draft orders can be confirmed.');
            $updated = $this->repo->update($orderId, ['status' => 'confirmed', 'confirmed_at' => now()]);

            // Build line data for cross-module listeners (e.g. auto-create delivery order).
            // Skip lines with non-positive qty — both zero and negative quantities are
            // intentionally excluded as they represent empty or invalid order entries.
            $lines = [];
            foreach (($updated->lines ?? []) as $line) {
                $qty = (string) ($line->qty ?? '0');
                if (bccomp($qty, '0', 8) <= 0) {
                    continue;
                }
                $lines[] = [
                    'product_id'  => $line->product_id ?? null,
                    'description' => $line->description ?? '',
                    'qty'         => $qty,
                    'uom'         => $line->uom ?? 'pcs',
                    'unit_price'  => isset($line->unit_price) ? (string) $line->unit_price : null,
                    'tax_rate'    => isset($line->tax_rate) ? (string) $line->tax_rate : null,
                ];
            }

            Event::dispatch(new OrderConfirmed(
                orderId:             $orderId,
                tenantId:            (string) ($order->tenant_id ?? ''),
                customerId:          $order->customer_id ?? null,
                promisedDeliveryDate: isset($order->promised_delivery_date)
                    ? (string) $order->promised_delivery_date
                    : null,
                lines:               $lines,
            ));
            return $updated;
        });
    }
}
