<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Repositories;

interface PurchaseAggregateRepositoryInterface
{
    /** @param array<string, mixed> $payload */
    public function createPurchaseOrder(array $payload): array;

    /** @param array<string, mixed> $payload */
    public function updatePurchaseOrder(int $id, array $payload, ?int $expectedVersion = null): array;

    public function findPurchaseOrderForUpdate(int $id): ?array;

    public function findPurchaseOrder(int $id): ?array;

    /** @param array<string, mixed> $attributes */
    public function updatePurchaseOrderHeader(int $id, array $attributes): array;

    /** @param array<int, array<string, mixed>> $lines */
    public function replacePurchaseOrderLines(int $purchaseOrderId, array $lines): void;

    /** @param array<string, mixed> $payload */
    public function createGrn(array $payload): array;

    public function findGrnForUpdate(int $id): ?array;

    /** @param array<string, mixed> $attributes */
    public function updateGrnHeader(int $id, array $attributes): array;

    /** @param array<string, mixed> $attributes */
    public function updatePurchaseOrderLine(int $id, array $attributes): array;

    /** @param array<string, mixed> $payload */
    public function createPurchaseReturn(array $payload): array;

    public function findPurchaseReturnForUpdate(int $id): ?array;

    /** @param array<string, mixed> $attributes */
    public function updatePurchaseReturnHeader(int $id, array $attributes): array;

    /** @param array<string, mixed> $payload */
    public function createPurchaseInvoice(array $payload): array;

    public function findInvoiceForUpdate(int $id): ?array;

    /** @param array<string, mixed> $attributes */
    public function updateInvoice(int $id, array $attributes): array;

    /** @param array<string, mixed> $payload */
    public function createPayment(array $payload): array;
}
