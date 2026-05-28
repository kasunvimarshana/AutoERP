<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PurchaseWorkflowServiceInterface
{
    /** @param array<string, mixed> $payload */
    public function transition(string $entityType, int|string $id, array $payload): Result;

    /** @param array<string, mixed> $payload */
    public function createDocument(string $entityType, int|string $id, array $payload): Result;

    /** @param array<string, mixed> $payload */
    public function allocatePayment(string $entityType, int|string $id, array $payload): Result;

    /** @param array<string, mixed> $payload */
    public function postInventory(string $entityType, int|string $id, array $payload): Result;

    /** @param array<string, mixed> $payload */
    public function postFinance(string $entityType, int|string $id, array $payload): Result;

    /** @param array<string, mixed> $payload */
    public function reverseFinance(string $entityType, int|string $id, array $payload): Result;
}
