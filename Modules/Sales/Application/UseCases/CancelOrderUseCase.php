<?php
namespace Modules\Sales\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Sales\Domain\Contracts\SalesOrderRepositoryInterface;
use Modules\Sales\Domain\Events\OrderCancelled;
class CancelOrderUseCase
{
    public function __construct(private SalesOrderRepositoryInterface $repo) {}
    public function execute(string $orderId, ?string $reason = null): object
    {
        return DB::transaction(function () use ($orderId, $reason) {
            $order = $this->repo->findById($orderId);
            if (!$order) throw new \RuntimeException('Order not found.');
            if (in_array($order->status, ['shipped', 'invoiced', 'closed', 'cancelled'])) {
                throw new \RuntimeException('Cannot cancel order in status: '.$order->status);
            }
            $updated = $this->repo->update($orderId, ['status' => 'cancelled', 'cancellation_reason' => $reason]);
            Event::dispatch(new OrderCancelled($orderId, $reason));
            return $updated;
        });
    }
}
