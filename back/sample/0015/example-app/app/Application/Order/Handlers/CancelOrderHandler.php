<?php

namespace App\Application\Order\Handlers;

use App\Application\Order\Commands\CancelOrderCommand;
use App\Domain\Order\Exceptions\OrderException;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Order\ValueObjects\OrderId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class CancelOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {
    }

    public function handle(CancelOrderCommand $command): void
    {
        DB::transaction(function () use ($command) {
            $order = $this->orders->findById(OrderId::from($command->orderId))
                ?? throw OrderException::notFound($command->orderId);

            $order->cancel($command->reason);

            $this->orders->save($order);

            foreach ($order->pullDomainEvents() as $event) {
                Event::dispatch($event);
            }
        });
    }
}
