<?php

namespace App\Application\Order\Handlers;

use App\Application\Order\Commands\PlaceOrderCommand;
use App\Domain\Order\Entities\Order;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * PlaceOrderHandler — CQRS write handler.
 *
 * Orchestrates placing a new order:
 *   1. Creates the Order aggregate via the domain factory
 *   2. Persists it via the repository interface
 *   3. Dispatches domain events for side-effects (emails, analytics, etc.)
 */
final class PlaceOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {
    }

    public function handle(PlaceOrderCommand $command): string
    {
        return DB::transaction(function () use ($command) {
            $order = Order::place(customerId: $command->customerId);

            $this->orders->save($order);

            foreach ($order->pullDomainEvents() as $event) {
                Event::dispatch($event);
            }

            return $order->id()->value();
        });
    }
}
