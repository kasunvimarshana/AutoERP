<?php

namespace App\Application\Order\Handlers;

use App\Application\Order\Queries\GetOrderQuery;
use App\Domain\Order\Entities\Order;
use App\Domain\Order\Exceptions\OrderException;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Order\ValueObjects\OrderId;

final class GetOrderQueryHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {
    }

    /**
     * @throws OrderException when the order does not exist
     */
    public function handle(GetOrderQuery $query): Order
    {
        return $this->orders->findById(OrderId::from($query->orderId))
            ?? throw OrderException::notFound($query->orderId);
    }
}
