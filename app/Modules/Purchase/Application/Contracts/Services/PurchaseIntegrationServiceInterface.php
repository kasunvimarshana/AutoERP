<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface PurchaseIntegrationServiceInterface
{
    public function listAllSourceDocuments(array $payload): Result;

    public function showLinkedSourceDocument(int $documentId, array $payload): Result;

    public function listSourceDocuments(string $entityType, int|string $id, array $payload): Result;

    public function showSourceDocument(string $entityType, int|string $id, int $documentId, array $payload): Result;

    public function createSourceDocument(string $entityType, int|string $id, array $payload): Result;

    public function changeSourceDocumentStatus(
        string $entityType,
        int|string $id,
        int $documentId,
        array $payload,
    ): Result;

    public function matchSourceDocumentLine(
        string $entityType,
        int|string $id,
        int $documentId,
        array $payload,
    ): Result;

    public function unmatchSourceDocumentLine(
        string $entityType,
        int|string $id,
        int $documentId,
        array $payload,
    ): Result;

    public function createSourcePayment(string $entityType, int|string $id, array $payload): Result;

    public function createSourceAdvance(string $entityType, int|string $id, array $payload): Result;

    public function allocateSourcePayment(string $entityType, int|string $id, array $payload): Result;

    public function applySourceAdvance(string $entityType, int|string $id, array $payload): Result;

    public function listSourcePaymentAllocations(string $entityType, int|string $id, array $payload): Result;

    public function sourcePaymentSummary(string $entityType, int|string $id, array $payload): Result;

    public function supplierPayables(int $tenantId, ?int $supplierId): Result;

    public function supplierAdvanceBalances(int $tenantId, ?int $supplierId): Result;

    public function postPayment(int|string $paymentId, array $payload): Result;

    public function reversePayment(int|string $paymentId, array $payload): Result;

    public function refundPayment(int|string $paymentId, array $payload): Result;
}
