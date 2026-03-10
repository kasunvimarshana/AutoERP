<?php

declare(strict_types=1);

namespace App\Application\Order\Services;

use App\Application\Order\DTOs\CreateOrderDTO;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Order\Saga\SagaFailedException;
use App\Infrastructure\Persistence\Models\Order;
use App\Infrastructure\Persistence\Models\OrderItem;
use App\Infrastructure\Saga\SagaOrchestrator;
use App\Infrastructure\Saga\Steps\ConfirmOrderStep;
use App\Infrastructure\Saga\Steps\CreateOrderStep;
use App\Infrastructure\Saga\Steps\ProcessPaymentStep;
use App\Infrastructure\Saga\Steps\ReserveInventoryStep;
use App\Infrastructure\Messaging\EventPublisher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * OrderService
 *
 * Orchestrates order creation using the Saga pattern to coordinate
 * cross-service operations: inventory reservation and payment processing.
 */
class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventPublisher           $eventPublisher,
        private readonly string                   $inventoryServiceUrl,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Queries
    // ─────────────────────────────────────────────────────────────────────────

    public function list(
        string $tenantId,
        array  $filters  = [],
        int    $perPage  = 15
    ): LengthAwarePaginator {
        return $this->orderRepository->listForTenant($tenantId, $filters, $perPage);
    }

    public function findById(string $id, string $tenantId): Order
    {
        $order = $this->orderRepository->findBy(
            ['id' => $id, 'tenant_id' => $tenantId],
            ['*'],
            ['items']
        );

        if ($order === null) {
            throw new \App\Domain\Order\Exceptions\OrderNotFoundException($id);
        }

        return $order;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Commands — Saga-driven order creation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create an order using the Saga pattern.
     *
     * Steps:
     *  1. CreateOrder       — persist order in 'pending' state
     *  2. ReserveInventory  — call Inventory Service to reserve stock
     *  3. ProcessPayment    — call Payment processor
     *  4. ConfirmOrder      — mark order as confirmed, publish event
     *
     * If any step fails, all previously completed steps are compensated
     * in reverse order.
     *
     * @param  CreateOrderDTO $dto
     * @return Order
     *
     * @throws SagaFailedException
     */
    public function createViaOrchestrator(CreateOrderDTO $dto): Order
    {
        $sagaId = Uuid::uuid4()->toString();

        // ── Compute totals ────────────────────────────────────────────────────
        $subtotal = collect($dto->items)->sum(
            fn ($item) => $item['unit_price'] * $item['quantity']
        );
        $taxAmount      = round($subtotal * 0.1, 2);  // 10% tax — configurable per tenant
        $shippingAmount = 0.0;
        $totalAmount    = $subtotal + $taxAmount + $shippingAmount;

        // ── Build saga context ────────────────────────────────────────────────
        $context = [
            'saga_id'          => $sagaId,
            'tenant_id'        => $dto->tenantId,
            'user_id'          => $dto->userId,
            'currency'         => $dto->currency,
            'subtotal'         => $subtotal,
            'tax_amount'       => $taxAmount,
            'shipping_amount'  => $shippingAmount,
            'total_amount'     => $totalAmount,
            'items'            => $dto->items,
            'notes'            => $dto->notes,
            'metadata'         => $dto->metadata,
            'shipping_address' => $dto->shippingAddress,
            'service_token'    => $dto->serviceToken ?? '',
        ];

        // ── Build and execute orchestrator ────────────────────────────────────
        $orchestrator = (new SagaOrchestrator())
            ->addStep(new CreateOrderStep($this->orderRepository))
            ->addStep(new ReserveInventoryStep($this->inventoryServiceUrl))
            ->addStep(new ProcessPaymentStep($this->orderRepository))
            ->addStep(new ConfirmOrderStep($this->orderRepository, $this->eventPublisher));

        $context = $orchestrator->execute($sagaId, $context);

        // ── Persist order items ───────────────────────────────────────────────
        $this->persistOrderItems($context['order_id'], $dto->items);

        return $this->orderRepository->findOrFail($context['order_id'], ['*'], ['items']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Bulk-insert order items after the order has been created.
     *
     * @param  string                            $orderId
     * @param  array<int, array<string, mixed>>  $items
     * @return void
     */
    private function persistOrderItems(string $orderId, array $items): void
    {
        $now   = now()->toDateTimeString();
        $rows  = [];

        foreach ($items as $item) {
            $rows[] = [
                'id'              => Uuid::uuid4()->toString(),
                'order_id'        => $orderId,
                'product_id'      => $item['product_id'],
                'product_name'    => $item['product_name']  ?? '',
                'product_code'    => $item['product_code']  ?? '',
                'product_sku'     => $item['product_sku']   ?? '',
                'quantity'        => $item['quantity'],
                'unit_price'      => $item['unit_price'],
                'discount_amount' => $item['discount_amount'] ?? 0,
                'tax_amount'      => round($item['unit_price'] * $item['quantity'] * 0.1, 2),
                'line_total'      => $item['unit_price'] * $item['quantity'],
                'currency'        => $item['currency'] ?? 'USD',
                'metadata'        => json_encode($item['metadata'] ?? []),
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        OrderItem::insert($rows);
    }
}
