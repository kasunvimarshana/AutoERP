<?php

namespace App\Domain\Order\Repositories;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Shared\Contracts\RepositoryContract;

/**
 * Order Repository Interface — Domain layer contract.
 *
 * Only business-driven query methods live here.
 * Implementations live in Infrastructure/Persistence/Repositories/.
 *
 * @extends RepositoryContract<Order>
 */
interface OrderRepositoryInterface extends RepositoryContract
{
    public function findById(OrderId $id): ?Order;

    public function save(Order $order): void;

    public function delete(Order $order): void;

    /** @return array<Order> */
    public function findAll(): array;

    /** @return array<Order> */
    public function findByCustomerId(string $customerId): array;

    /** @return array<Order> */
    public function findByStatus(OrderStatus $status): array;

    public function nextIdentity(): OrderId;
}
