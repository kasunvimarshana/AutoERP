<?php

namespace App\Outbox;

use App\Events\BaseEvent;
use App\Models\Inventory;
use App\Models\OutboxMessage;

/**
 * Writes domain events to the outbox table inside the same database transaction
 * as the business operation, solving the dual-write problem.
 *
 * Usage — call inside an open DB::transaction():
 *
 *   DB::transaction(function () use ($data) {
 *       $order = Order::create($data);
 *       app(OutboxPublisher::class)->store(new OrderCreatedEvent($order), 'Order', $order->id);
 *   });
 *
 * The OutboxProcessor will later pick up pending rows and forward them to the
 * configured message broker.
 */
class OutboxPublisher
{
    /**
     * Persist a domain event to the outbox table.
     *
     * @param  BaseEvent  $event          The domain event to store.
     * @param  string     $aggregateType  Model class name (e.g. 'Order').
     * @param  int|null   $aggregateId    Primary key of the aggregate root.
     */
    public function store(BaseEvent $event, string $aggregateType, ?int $aggregateId = null): OutboxMessage
    {
        return OutboxMessage::create([
            'tenant_id'      => $event->tenantId,
            'aggregate_type' => $aggregateType,
            'aggregate_id'   => $aggregateId,
            'event_type'     => $event->getEventType(),
            'payload'        => $event->toPayload(),
            'status'         => OutboxMessage::STATUS_PENDING,
            'attempts'       => 0,
        ]);
    }

    /**
     * Write a low-stock alert entry to the outbox for the given inventory record.
     * Used when inventory quantity drops to or below the product's low-stock threshold.
     */
    public function storeLowStock(Inventory $inventory, ?int $tenantId): OutboxMessage
    {
        $payload = [
            'event_id'    => \Illuminate\Support\Str::uuid()->toString(),
            'event_type'  => 'inventory.low_stock',
            'occurred_at' => now()->toIso8601String(),
            'tenant_id'   => $tenantId,
            'data'        => [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'quantity'      => $inventory->quantity,
                'available'     => $inventory->available_quantity,
                'reserved'      => $inventory->reserved_quantity,
                'threshold'     => $inventory->product->low_stock_threshold ?? 10,
            ],
        ];

        return OutboxMessage::create([
            'tenant_id'      => $tenantId,
            'aggregate_type' => Inventory::class,
            'aggregate_id'   => $inventory->id,
            'event_type'     => 'inventory.low_stock',
            'payload'        => $payload,
            'status'         => OutboxMessage::STATUS_PENDING,
            'attempts'       => 0,
        ]);
    }
}
