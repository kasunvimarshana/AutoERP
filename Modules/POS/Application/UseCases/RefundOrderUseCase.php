<?php

namespace Modules\POS\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\POS\Domain\Contracts\PosOrderRepositoryInterface;
use Modules\POS\Domain\Events\PosOrderRefunded;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

class RefundOrderUseCase implements UseCaseInterface
{
    public function __construct(
        private PosOrderRepositoryInterface $orderRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $order = $this->orderRepo->findById($data['order_id']);
        if (!$order) {
            throw new \DomainException('Order not found.');
        }

        if ($order->status !== 'paid') {
            throw new \DomainException('Only paid orders can be refunded.');
        }

        return DB::transaction(function () use ($order) {
            $updated = $this->orderRepo->update($order->id, [
                'status' => 'refunded',
            ]);

            Event::dispatch(new PosOrderRefunded($order->id));

            return $updated;
        });
    }
}
