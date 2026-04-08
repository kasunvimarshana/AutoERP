<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Order\Entities\Order;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Order\ValueObjects\OrderId;
use App\Infrastructure\Persistence\Eloquent\OrderModel;

/**
 * EloquentOrderRepository — Infrastructure implementation of OrderRepositoryInterface.
 *
 * Translates between the Eloquent persistence model (OrderModel)
 * and the Domain entity (Order). No business logic here.
 */
final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly OrderModel $model,
    ) {
    }

    // -------------------------------------------------------------------------
    // RepositoryContract
    // -------------------------------------------------------------------------

    public function findById(mixed $id): ?Order
    {
        $idValue = $id instanceof OrderId ? $id->value() : (string) $id;
        $record  = $this->model->find($idValue);

        return $record ? $this->toDomain($record) : null;
    }

    public function save(mixed $entity): void
    {
        $this->model->newQuery()->updateOrInsert(
            ['id' => $entity->id()->value()],
            $this->toPersistence($entity),
        );
    }

    public function delete(mixed $entity): void
    {
        $this->model->newQuery()
            ->where('id', $entity->id()->value())
            ->delete();
    }

    // -------------------------------------------------------------------------
    // OrderRepositoryInterface
    // -------------------------------------------------------------------------

    public function findAll(): array
    {
        return $this->model->all()
            ->map(fn (OrderModel $r) => $this->toDomain($r))
            ->all();
    }

    public function findByCustomerId(string $customerId): array
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->get()
            ->map(fn (OrderModel $r) => $this->toDomain($r))
            ->all();
    }

    public function findByStatus(OrderStatus $status): array
    {
        return $this->model->newQuery()
            ->where('status', $status->value)
            ->get()
            ->map(fn (OrderModel $r) => $this->toDomain($r))
            ->all();
    }

    public function nextIdentity(): OrderId
    {
        return OrderId::generate();
    }

    // -------------------------------------------------------------------------
    // Mapping
    // -------------------------------------------------------------------------

    private function toDomain(OrderModel $record): Order
    {
        return Order::reconstitute(
            id: $record->id,
            customerId: $record->customer_id,
            status: $record->status,
            placedAt: $record->placed_at->toIso8601String(),
        );
    }

    private function toPersistence(Order $order): array
    {
        return [
            'id'                  => $order->id()->value(),
            'customer_id'         => $order->customerId(),
            'status'              => $order->status()->value,
            'total_amount_cents'  => $order->total()->amount(),
            'total_currency'      => $order->total()->currency(),
            'placed_at'           => $order->placedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
