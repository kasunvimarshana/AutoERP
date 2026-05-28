<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Document\Application\Services\DocumentOrchestrator;
use Modules\Document\Domain\Aggregates\DocumentAggregate;
use Modules\Payment\Application\Contracts\Services\PaymentPostingServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentReversalServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentServiceInterface;
use Modules\Payment\Application\Contracts\Services\RefundServiceInterface;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\AdvancePayments\ListAdvancePaymentsServiceInterface;
use Modules\Sales\Application\Contracts\Services\SalesIntegrationServiceInterface;
use Modules\Sales\Application\Contracts\Services\SalesWorkflowServiceInterface;
use Modules\Sales\Application\Repositories\GdnHeaderRepositoryInterface;
use Modules\Sales\Application\Repositories\GdnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesDocumentLinkRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesPaymentAllocationRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Throwable;

final class SalesIntegrationService implements SalesIntegrationServiceInterface
{
    private const ENTITY_TYPES = ['sales_order', 'gdn_header', 'sales_return'];

    public function __construct(
        private readonly SalesWorkflowServiceInterface $workflowService,
        private readonly DocumentOrchestrator $documentOrchestrator,
        private readonly SalesDocumentLinkRepositoryInterface $purchaseDocumentLinkRepository,
        private readonly SalesPaymentAllocationRepositoryInterface $purchasePaymentAllocationRepository,
        private readonly SalesOrderRepositoryInterface $purchaseOrderRepository,
        private readonly SalesOrderLineRepositoryInterface $purchaseOrderLineRepository,
        private readonly GdnHeaderRepositoryInterface $gdnHeaderRepository,
        private readonly GdnLineRepositoryInterface $gdnLineRepository,
        private readonly SalesReturnRepositoryInterface $purchaseReturnRepository,
        private readonly SalesReturnLineRepositoryInterface $purchaseReturnLineRepository,
        private readonly PaymentServiceInterface $paymentService,
        private readonly AdvancePaymentServiceInterface $advancePaymentService,
        private readonly PaymentPostingServiceInterface $paymentPostingService,
        private readonly PaymentReversalServiceInterface $paymentReversalService,
        private readonly RefundServiceInterface $refundService,
        private readonly ListAdvancePaymentsServiceInterface $listAdvancePaymentsService,
    ) {
    }

    public function listSourceDocuments(string $entityType, int|string $id, array $payload): Result
    {
        try {
            $entityResult = $this->resolveScopedEntity($entityType, $id, $payload);
            if ($entityResult->isFailure()) {
                return $entityResult;
            }

            [$record, $tenantId] = $entityResult->valueOrFail();
            $links = $this->sourceDocumentLinks($tenantId, $entityType, (int) $record->id());

            $documents = [];
            $seenDocumentIds = [];
            foreach ($links as $link) {
                $documentId = (int) $link->get('document_id', 0);
                if ($documentId < 1 || isset($seenDocumentIds[$documentId])) {
                    continue;
                }

                $seenDocumentIds[$documentId] = true;
                $aggregate = $this->documentOrchestrator->show($tenantId, $documentId);
                $documents[] = $this->serializeDocumentAggregate($aggregate, $link);
            }

            return Result::success($documents);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function showSourceDocument(string $entityType, int|string $id, int $documentId, array $payload): Result
    {
        try {
            $entityResult = $this->resolveScopedEntity($entityType, $id, $payload);
            if ($entityResult->isFailure()) {
                return $entityResult;
            }

            [$record, $tenantId] = $entityResult->valueOrFail();
            $headerLink = $this->findHeaderLink($tenantId, $entityType, (int) $record->id(), $documentId);
            if (! $headerLink instanceof DataRecord) {
                return Result::failure(new Error(
                    SalesErrorCode::NOT_FOUND,
                    'Document is not linked with the requested sales source.',
                ));
            }

            $aggregate = $this->documentOrchestrator->show($tenantId, $documentId);
            $data = $this->serializeDocumentAggregate($aggregate, $headerLink);
            $data['line_links'] = $this->serializeLineLinks($tenantId, $entityType, (int) $record->id(), $documentId);

            return Result::success($data);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function createSourceDocument(string $entityType, int|string $id, array $payload): Result
    {
        return $this->workflowService->createDocument($entityType, $id, $payload);
    }

    public function changeSourceDocumentStatus(
        string $entityType,
        int|string $id,
        int $documentId,
        array $payload,
    ): Result {
        try {
            $entityResult = $this->resolveScopedEntity($entityType, $id, $payload);
            if ($entityResult->isFailure()) {
                return $entityResult;
            }

            [$record, $tenantId] = $entityResult->valueOrFail();
            $headerLink = $this->findHeaderLink($tenantId, $entityType, (int) $record->id(), $documentId);
            if (! $headerLink instanceof DataRecord) {
                return Result::failure(new Error(
                    SalesErrorCode::NOT_FOUND,
                    'Document is not linked with the requested sales source.',
                ));
            }

            $status = strtolower(trim((string) ($payload['status'] ?? '')));
            if ($status === '') {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'status is required.'));
            }

            $aggregate = $this->documentOrchestrator->changeStatus(
                $tenantId,
                $documentId,
                $status,
                isset($payload['action_name']) ? (string) $payload['action_name'] : null,
            );

            return Result::success($this->serializeDocumentAggregate($aggregate, $headerLink));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function matchSourceDocumentLine(
        string $entityType,
        int|string $id,
        int $documentId,
        array $payload,
    ): Result {
        try {
            $entityResult = $this->resolveScopedEntity($entityType, $id, $payload);
            if ($entityResult->isFailure()) {
                return $entityResult;
            }

            [$record, $tenantId] = $entityResult->valueOrFail();
            $sourceId = (int) $record->id();

            $headerLink = $this->findHeaderLink($tenantId, $entityType, $sourceId, $documentId);
            if (! $headerLink instanceof DataRecord) {
                return Result::failure(new Error(
                    SalesErrorCode::NOT_FOUND,
                    'Document is not linked with the requested sales source.',
                ));
            }

            $sourceLineId = (int) ($payload['source_line_id'] ?? 0);
            $documentLineId = (int) ($payload['document_line_id'] ?? 0);
            $linkedQuantity = round((float) ($payload['linked_quantity'] ?? 0), 4);

            if ($sourceLineId < 1 || $documentLineId < 1 || $linkedQuantity <= 0) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'source_line_id, document_line_id and linked_quantity are required.',
                ));
            }

            $sourceLine = $this->findSourceLine($entityType, $sourceLineId, $sourceId);
            if (! $sourceLine instanceof DataRecord) {
                return Result::failure(new Error(
                    SalesErrorCode::NOT_FOUND,
                    'Source line not found for this sales entity.',
                ));
            }

            $aggregate = $this->documentOrchestrator->show($tenantId, $documentId);
            $documentItem = $this->findDocumentItem($aggregate, $documentLineId);
            if ($documentItem === null) {
                return Result::failure(new Error(
                    SalesErrorCode::NOT_FOUND,
                    'Document line not found for this customer document.',
                ));
            }

            $existingMatch = $this->findActiveLineMatch(
                $tenantId,
                $entityType,
                $sourceId,
                $documentId,
                $sourceLineId,
                $documentLineId,
            );
            if ($existingMatch instanceof DataRecord) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'This source line is already matched with the selected document line.',
                ));
            }

            $availableQuantity = $this->resolveAvailableSourceLineQuantity(
                $tenantId,
                $entityType,
                $sourceId,
                $sourceLine,
            );
            if ($linkedQuantity > $availableQuantity + 0.0001) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'linked_quantity exceeds available source line quantity for document matching.',
                ));
            }

            $resolvedLinkedAmount = $this->resolveLinkedAmount($payload, $linkedQuantity, $documentItem);

            $created = $this->purchaseDocumentLinkRepository->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $record->get('organization_unit_id'),
                'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                'source_type' => $entityType,
                'source_id' => $sourceId,
                'source_line_id' => $sourceLineId,
                'document_id' => $documentId,
                'document_line_id' => $documentLineId,
                'linked_quantity' => $linkedQuantity,
                'linked_amount' => $resolvedLinkedAmount,
                'status' => 'active',
                'linked_at' => now()->toDateTimeString(),
                'created_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
            ]);

            return Result::success([
                'match' => $created->toArray(),
                'available_quantity_after' => round(max(0.0, $availableQuantity - $linkedQuantity), 4),
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function unmatchSourceDocumentLine(
        string $entityType,
        int|string $id,
        int $documentId,
        array $payload,
    ): Result {
        try {
            $entityResult = $this->resolveScopedEntity($entityType, $id, $payload);
            if ($entityResult->isFailure()) {
                return $entityResult;
            }

            [$record, $tenantId] = $entityResult->valueOrFail();
            $sourceId = (int) $record->id();

            $headerLink = $this->findHeaderLink($tenantId, $entityType, $sourceId, $documentId);
            if (! $headerLink instanceof DataRecord) {
                return Result::failure(new Error(
                    SalesErrorCode::NOT_FOUND,
                    'Document is not linked with the requested sales source.',
                ));
            }

            $match = null;
            $linkId = (int) ($payload['link_id'] ?? 0);
            if ($linkId > 0) {
                $byId = $this->purchaseDocumentLinkRepository->findById($linkId);
                if (
                    $byId instanceof DataRecord
                    && (int) $byId->get('tenant_id', 0) === $tenantId
                    && (string) $byId->get('source_type', '') === $entityType
                    && (int) $byId->get('source_id', 0) === $sourceId
                    && (int) $byId->get('document_id', 0) === $documentId
                    && (string) $byId->get('status', '') === 'active'
                ) {
                    $match = $byId;
                }
            }

            if (! $match instanceof DataRecord) {
                $sourceLineId = (int) ($payload['source_line_id'] ?? 0);
                $documentLineId = (int) ($payload['document_line_id'] ?? 0);
                if ($sourceLineId < 1 || $documentLineId < 1) {
                    return Result::failure(new Error(
                        SalesErrorCode::INVALID_VALUE,
                        'link_id or (source_line_id and document_line_id) is required.',
                    ));
                }

                $match = $this->findActiveLineMatch(
                    $tenantId,
                    $entityType,
                    $sourceId,
                    $documentId,
                    $sourceLineId,
                    $documentLineId,
                );
            }

            if (! $match instanceof DataRecord) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Document line match not found.'));
            }

            $updated = $this->purchaseDocumentLinkRepository->update((int) $match->id(), [
                'status' => 'reversed',
                'metadata' => array_merge(
                    is_array($match->get('metadata', [])) ? $match->get('metadata', []) : [],
                    is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                ),
                'updated_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
            ]);

            return Result::success([
                'unmatched' => true,
                'match' => $updated->toArray(),
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function createSourcePayment(string $entityType, int|string $id, array $payload): Result
    {
        try {
            $entityResult = $this->resolveScopedEntity($entityType, $id, $payload);
            if ($entityResult->isFailure()) {
                return $entityResult;
            }

            [$record, $tenantId] = $entityResult->valueOrFail();
            $sourceId = (int) $record->id();
            $documentId = isset($payload['document_id']) ? (int) $payload['document_id'] : 0;

            if ($documentId < 1) {
                $headerLinks = $this->sourceDocumentLinks($tenantId, $entityType, $sourceId);
                $last = end($headerLinks);
                $documentId = $last instanceof DataRecord ? (int) $last->get('document_id', 0) : 0;
            }

            if ($documentId < 1) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'A linked customer document is required before creating payment.',
                ));
            }

            $amount = round((float) ($payload['amount'] ?? $payload['allocated_amount'] ?? 0), 4);
            if ($amount <= 0) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'amount must be greater than zero.',
                ));
            }

            $paymentPayload = [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $payload['organization_unit_id'] ?? $record->get('organization_unit_id'),
                'party_type' => 'customer',
                'party_id' => $record->get('customer_id'),
                'payment_number' => $payload['payment_number'] ?? null,
                'payment_date' => $payload['payment_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'currency_id' => $payload['currency_id'] ?? $record->get('currency_id'),
                'exchange_rate' => $payload['exchange_rate'] ?? null,
                'base_amount' => $payload['base_amount'] ?? $amount,
                'payment_method_id' => $payload['payment_method_id'] ?? null,
                'account_id' => $payload['account_id'] ?? null,
                'status' => $payload['status'] ?? 'draft',
                'reference' => $payload['reference'] ?? $record->get('reference'),
                'notes' => $payload['notes'] ?? null,
                'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                'created_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
            ];

            $paymentResult = $this->paymentService->createPayment($paymentPayload);
            if ($paymentResult->isFailure()) {
                return $paymentResult;
            }

            $payment = $paymentResult->valueOrFail();
            if (! $payment instanceof DataRecord) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Unexpected payment response.'));
            }

            if (($payload['allocate_now'] ?? true) !== false) {
                $allocatePayload = [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $paymentPayload['organization_unit_id'],
                    'payment_id' => (int) $payment->id(),
                    'document_id' => $documentId,
                    'allocated_amount' => round((float) ($payload['allocated_amount'] ?? $amount), 4),
                    'currency_id' => $payload['currency_id'] ?? null,
                    'base_allocated_amount' => $payload['base_allocated_amount'] ?? null,
                    'reference' => $payload['reference'] ?? null,
                    'metadata' => $paymentPayload['metadata'],
                    'actor_id' => $paymentPayload['created_by'],
                    'idempotency_key' => $payload['idempotency_key'] ?? null,
                ];

                $allocationResult = $this->workflowService->allocatePayment($entityType, $id, $allocatePayload);
                if ($allocationResult->isFailure()) {
                    return $allocationResult;
                }
            }

            return Result::success([
                'payment' => $payment->toArray(),
                'document_id' => $documentId,
                'allocated' => ($payload['allocate_now'] ?? true) !== false,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function createSourceAdvance(string $entityType, int|string $id, array $payload): Result
    {
        try {
            $entityResult = $this->resolveScopedEntity($entityType, $id, $payload);
            if ($entityResult->isFailure()) {
                return $entityResult;
            }

            [$record, $tenantId] = $entityResult->valueOrFail();
            $sourceId = (int) $record->id();
            $documentId = isset($payload['document_id']) ? (int) $payload['document_id'] : 0;

            if ($documentId < 1) {
                $headerLinks = $this->sourceDocumentLinks($tenantId, $entityType, $sourceId);
                $last = end($headerLinks);
                $documentId = $last instanceof DataRecord ? (int) $last->get('document_id', 0) : 0;
            }

            if ($documentId < 1) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'A linked customer document is required before creating advance payment.',
                ));
            }

            $amount = round((float) ($payload['amount'] ?? $payload['allocated_amount'] ?? 0), 4);
            if ($amount <= 0) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'amount must be greater than zero.',
                ));
            }

            $advanceNumber = trim((string) ($payload['advance_number'] ?? ''));
            if ($advanceNumber === '') {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'advance_number is required.',
                ));
            }

            $customerId = (int) $record->get('customer_id', 0);
            if ($customerId < 1) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'customer_id is required on source entity for advance creation.',
                ));
            }

            $advancePayload = [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $payload['organization_unit_id'] ?? $record->get('organization_unit_id'),
                'party_type' => 'customer',
                'party_id' => $customerId,
                'advance_number' => $advanceNumber,
                'advance_date' => $payload['advance_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'remaining_amount' => $payload['remaining_amount'] ?? $amount,
                'currency_id' => $payload['currency_id'] ?? $record->get('currency_id'),
                'exchange_rate' => $payload['exchange_rate'] ?? null,
                'base_amount' => $payload['base_amount'] ?? $amount,
                'reference' => $payload['reference'] ?? $record->get('reference'),
                'notes' => $payload['notes'] ?? null,
                'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                'created_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
            ];

            $advanceResult = $this->advancePaymentService->createAdvance($advancePayload);
            if ($advanceResult->isFailure()) {
                return $advanceResult;
            }

            $advance = $advanceResult->valueOrFail();
            if (! $advance instanceof DataRecord) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Unexpected advance response.'));
            }

            if (($payload['allocate_now'] ?? false) === true) {
                $allocationResult = $this->workflowService->allocatePayment($entityType, $id, [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $advancePayload['organization_unit_id'],
                    'advance_payment_id' => (int) $advance->id(),
                    'document_id' => $documentId,
                    'allocated_amount' => round((float) ($payload['allocated_amount'] ?? $amount), 4),
                    'currency_id' => $payload['currency_id'] ?? null,
                    'base_allocated_amount' => $payload['base_allocated_amount'] ?? null,
                    'reference' => $payload['reference'] ?? null,
                    'metadata' => $advancePayload['metadata'],
                    'actor_id' => $advancePayload['created_by'],
                    'idempotency_key' => $payload['idempotency_key'] ?? null,
                ]);

                if ($allocationResult->isFailure()) {
                    return $allocationResult;
                }
            }

            return Result::success([
                'advance_payment' => $advance->toArray(),
                'document_id' => $documentId,
                'allocated' => ($payload['allocate_now'] ?? false) === true,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function allocateSourcePayment(string $entityType, int|string $id, array $payload): Result
    {
        return $this->workflowService->allocatePayment($entityType, $id, $payload);
    }

    public function applySourceAdvance(string $entityType, int|string $id, array $payload): Result
    {
        if (! isset($payload['advance_payment_id'])) {
            return Result::failure(new Error(
                SalesErrorCode::INVALID_VALUE,
                'advance_payment_id is required.',
            ));
        }

        return $this->workflowService->allocatePayment($entityType, $id, $payload);
    }

    public function listSourcePaymentAllocations(string $entityType, int|string $id, array $payload): Result
    {
        try {
            $entityResult = $this->resolveScopedEntity($entityType, $id, $payload);
            if ($entityResult->isFailure()) {
                return $entityResult;
            }

            [$record, $tenantId] = $entityResult->valueOrFail();
            $sourceId = (int) $record->id();
            $headerLinks = $this->sourceDocumentLinks($tenantId, $entityType, $sourceId);

            $allocations = [];
            foreach ($headerLinks as $link) {
                $documentId = (int) $link->get('document_id', 0);
                if ($documentId < 1) {
                    continue;
                }

                $documentAllocations = $this->purchasePaymentAllocationRepository->list([
                    'tenant_id' => $tenantId,
                    'document_id' => $documentId,
                    'status' => 'active',
                ]);

                foreach ($documentAllocations as $allocation) {
                    if (! $allocation instanceof DataRecord) {
                        continue;
                    }

                    $row = $allocation->toArray();
                    $row['document_id'] = $documentId;
                    $allocations[] = $row;
                }
            }

            return Result::success($allocations);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function sourcePaymentSummary(string $entityType, int|string $id, array $payload): Result
    {
        try {
            $entityResult = $this->resolveScopedEntity($entityType, $id, $payload);
            if ($entityResult->isFailure()) {
                return $entityResult;
            }

            [$record, $tenantId] = $entityResult->valueOrFail();
            $sourceId = (int) $record->id();
            $headerLinks = $this->sourceDocumentLinks($tenantId, $entityType, $sourceId);

            $linkedAmount = 0.0;
            $allocatedAmount = 0.0;

            foreach ($headerLinks as $link) {
                $linkedAmount += (float) $link->get('linked_amount', 0);

                $documentId = (int) $link->get('document_id', 0);
                if ($documentId < 1) {
                    continue;
                }

                $documentAllocations = $this->purchasePaymentAllocationRepository->list([
                    'tenant_id' => $tenantId,
                    'document_id' => $documentId,
                    'status' => 'active',
                ]);

                foreach ($documentAllocations as $allocation) {
                    if (! $allocation instanceof DataRecord) {
                        continue;
                    }

                    $allocatedAmount += (float) $allocation->get('allocated_amount', 0);
                }
            }

            $linkedAmount = round($linkedAmount, 4);
            $allocatedAmount = round($allocatedAmount, 4);
            $outstandingAmount = round(max(0.0, $linkedAmount - $allocatedAmount), 4);

            return Result::success([
                'entity_type' => $entityType,
                'entity_id' => $sourceId,
                'linked_amount' => $linkedAmount,
                'allocated_amount' => $allocatedAmount,
                'outstanding_amount' => $outstandingAmount,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function customerReceivables(int $tenantId, ?int $customerId): Result
    {
        try {
            if ($tenantId < 1) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'tenant_id is required.'));
            }

            $sourceRecords = $this->purchaseOrderRepository->list([
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
            ]);

            $byCustomer = [];
            foreach ($sourceRecords as $source) {
                if (! $source instanceof DataRecord) {
                    continue;
                }

                $sourceId = (int) $source->id();
                $sourceCustomerId = (int) $source->get('customer_id', 0);
                if ($sourceId < 1 || $sourceCustomerId < 1) {
                    continue;
                }

                $summaryResult = $this->sourcePaymentSummary('sales_order', $sourceId, ['tenant_id' => $tenantId]);
                if ($summaryResult->isFailure()) {
                    continue;
                }

                $summary = $summaryResult->valueOrFail();
                if (! is_array($summary)) {
                    continue;
                }

                $outstanding = (float) ($summary['outstanding_amount'] ?? 0);
                if ($outstanding <= 0) {
                    continue;
                }

                if (! isset($byCustomer[$sourceCustomerId])) {
                    $byCustomer[$sourceCustomerId] = [
                        'customer_id' => $sourceCustomerId,
                        'outstanding_amount' => 0.0,
                    ];
                }

                $byCustomer[$sourceCustomerId]['outstanding_amount'] += $outstanding;
            }

            $result = [];
            foreach ($byCustomer as $row) {
                $row['outstanding_amount'] = round((float) $row['outstanding_amount'], 4);
                $result[] = $row;
            }

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function customerAdvanceBalances(int $tenantId, ?int $customerId): Result
    {
        try {
            if ($tenantId < 1) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'tenant_id is required.'));
            }

            $criteria = [
                'tenant_id' => $tenantId,
                'party_type' => 'customer',
            ];
            if ($customerId !== null) {
                $criteria['party_id'] = $customerId;
            }

            $advancesResult = $this->listAdvancePaymentsService->execute($criteria, 500, 1);
            if ($advancesResult->isFailure()) {
                return $advancesResult;
            }

            $pageResult = $advancesResult->valueOrFail();
            if (! $pageResult instanceof PagedResult) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Unexpected advance payment response.',
                ));
            }

            $balances = [];
            foreach ($pageResult->items as $item) {
                if (! $item instanceof DataRecord) {
                    continue;
                }

                $partyId = (int) $item->get('party_id', 0);
                if ($partyId < 1) {
                    continue;
                }

                if (! isset($balances[$partyId])) {
                    $balances[$partyId] = [
                        'customer_id' => $partyId,
                        'remaining_advance_amount' => 0.0,
                    ];
                }

                $balances[$partyId]['remaining_advance_amount'] += (float) $item->get('remaining_amount', 0);
            }

            $result = [];
            foreach ($balances as $row) {
                $row['remaining_advance_amount'] = round((float) $row['remaining_advance_amount'], 4);
                $result[] = $row;
            }

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function postPayment(int|string $paymentId, array $payload): Result
    {
        return $this->paymentPostingService->postPayment($paymentId, $payload);
    }

    public function reversePayment(int|string $paymentId, array $payload): Result
    {
        return $this->paymentReversalService->reversePayment($paymentId, $payload);
    }

    public function refundPayment(int|string $paymentId, array $payload): Result
    {
        return $this->refundService->refundPayment($paymentId, $payload);
    }

    private function resolveScopedEntity(string $entityType, int|string $id, array $payload): Result
    {
        if (! in_array($entityType, self::ENTITY_TYPES, true)) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Unsupported entity_type.'));
        }

        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        if ($tenantId < 1) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'tenant_id is required.'));
        }

        $record = $this->findEntity($entityType, $id);
        if (! $record instanceof DataRecord) {
            return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales entity not found.'));
        }

        if ((int) $record->get('tenant_id', 0) !== $tenantId) {
            return Result::failure(new Error(
                SalesErrorCode::INVALID_VALUE,
                'Cross-tenant integration is not allowed.',
            ));
        }

        return Result::success([$record, $tenantId]);
    }

    private function findEntity(string $entityType, int|string $id): ?DataRecord
    {
        return match ($entityType) {
            'sales_order' => $this->purchaseOrderRepository->findById($id),
            'gdn_header' => $this->gdnHeaderRepository->findById($id),
            'sales_return' => $this->purchaseReturnRepository->findById($id),
            default => null,
        };
    }

    private function findSourceLine(string $entityType, int $sourceLineId, int $sourceId): ?DataRecord
    {
        $line = match ($entityType) {
            'sales_order' => $this->purchaseOrderLineRepository->findById($sourceLineId),
            'gdn_header' => $this->gdnLineRepository->findById($sourceLineId),
            'sales_return' => $this->purchaseReturnLineRepository->findById($sourceLineId),
            default => null,
        };

        if (! $line instanceof DataRecord) {
            return null;
        }

        $isLinkedToSource = match ($entityType) {
            'sales_order' => (int) $line->get('sales_order_id', 0) === $sourceId,
            'gdn_header' => (int) $line->get('gdn_header_id', 0) === $sourceId,
            'sales_return' => (int) $line->get('sales_return_id', 0) === $sourceId,
            default => false,
        };

        return $isLinkedToSource ? $line : null;
    }

    private function findDocumentItem(DocumentAggregate $aggregate, int $documentLineId): ?object
    {
        foreach ($aggregate->items as $item) {
            if ((int) ($item->id ?? 0) === $documentLineId) {
                return $item;
            }
        }

        return null;
    }

    private function findActiveLineMatch(
        int $tenantId,
        string $entityType,
        int $sourceId,
        int $documentId,
        int $sourceLineId,
        int $documentLineId,
    ): ?DataRecord {
        $links = $this->purchaseDocumentLinkRepository->list([
            'tenant_id' => $tenantId,
            'source_type' => $entityType,
            'source_id' => $sourceId,
            'document_id' => $documentId,
            'source_line_id' => $sourceLineId,
            'document_line_id' => $documentLineId,
            'status' => 'active',
        ]);

        foreach ($links as $link) {
            if ($link instanceof DataRecord) {
                return $link;
            }
        }

        return null;
    }

    private function resolveAvailableSourceLineQuantity(
        int $tenantId,
        string $entityType,
        int $sourceId,
        DataRecord $sourceLine,
    ): float {
        $baseQuantity = match ($entityType) {
            'sales_order' => max(
                (float) $sourceLine->get('received_qty', 0),
                (float) $sourceLine->get('ordered_qty', 0),
            ),
            'gdn_header' => (float) $sourceLine->get('accepted_qty', $sourceLine->get('received_qty', 0)),
            'sales_return' => (float) $sourceLine->get('return_qty', 0),
            default => 0.0,
        };

        if ($baseQuantity <= 0) {
            return 0.0;
        }

        $links = $this->purchaseDocumentLinkRepository->list([
            'tenant_id' => $tenantId,
            'source_type' => $entityType,
            'source_id' => $sourceId,
            'source_line_id' => (int) $sourceLine->id(),
            'status' => 'active',
        ]);

        $matchedQuantity = 0.0;
        foreach ($links as $link) {
            if (! $link instanceof DataRecord) {
                continue;
            }

            $documentLineId = (int) $link->get('document_line_id', 0);
            if ($documentLineId < 1) {
                continue;
            }

            $matchedQuantity += (float) $link->get('linked_quantity', 0);
        }

        return round(max(0.0, $baseQuantity - $matchedQuantity), 4);
    }

    private function resolveLinkedAmount(array $payload, float $linkedQuantity, object $documentItem): float
    {
        if (isset($payload['linked_amount'])) {
            return round(max(0.0, (float) $payload['linked_amount']), 4);
        }

        $itemQuantity = (float) ($documentItem->data['quantity'] ?? 0);
        $lineTotal = round((float) ($documentItem->lineTotal ?? 0), 4);

        if ($itemQuantity > 0 && $lineTotal > 0) {
            $unitAmount = $lineTotal / $itemQuantity;
            return round(max(0.0, $unitAmount * $linkedQuantity), 4);
        }

        return max(0.0, $lineTotal);
    }

    /** @return list<DataRecord> */
    private function sourceDocumentLinks(int $tenantId, string $entityType, int $sourceId): array
    {
        $links = $this->purchaseDocumentLinkRepository->list([
            'tenant_id' => $tenantId,
            'source_type' => $entityType,
            'source_id' => $sourceId,
            'status' => 'active',
        ]);

        $headerLinks = [];
        foreach ($links as $link) {
            if (! $link instanceof DataRecord) {
                continue;
            }

            if ((int) $link->get('source_line_id', 0) > 0 || (int) $link->get('document_line_id', 0) > 0) {
                continue;
            }

            $headerLinks[] = $link;
        }

        return $headerLinks;
    }

    private function findHeaderLink(int $tenantId, string $entityType, int $sourceId, int $documentId): ?DataRecord
    {
        foreach ($this->sourceDocumentLinks($tenantId, $entityType, $sourceId) as $link) {
            if ((int) $link->get('document_id', 0) === $documentId) {
                return $link;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    private function serializeLineLinks(int $tenantId, string $entityType, int $sourceId, int $documentId): array
    {
        $links = $this->purchaseDocumentLinkRepository->list([
            'tenant_id' => $tenantId,
            'source_type' => $entityType,
            'source_id' => $sourceId,
            'document_id' => $documentId,
            'status' => 'active',
        ]);

        $result = [];
        foreach ($links as $link) {
            if (! $link instanceof DataRecord) {
                continue;
            }

            if ((int) $link->get('source_line_id', 0) < 1 && (int) $link->get('document_line_id', 0) < 1) {
                continue;
            }

            $result[] = $link->toArray();
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDocumentAggregate(DocumentAggregate $aggregate, DataRecord $headerLink): array
    {
        $items = [];
        foreach ($aggregate->items as $item) {
            $items[] = [
                'id' => $item->id,
                'document_id' => $item->documentId,
                'item_type' => $item->itemType,
                'description' => $item->description,
                'line_total' => $item->lineTotal,
                'sequence' => $item->sequence,
                'data' => $item->data,
            ];
        }

        return [
            'id' => $aggregate->document->id,
            'tenant_id' => $aggregate->document->tenantId,
            'document_type_id' => $aggregate->document->documentTypeId,
            'document_number' => $aggregate->document->documentNumber,
            'status' => $aggregate->document->status,
            'document_date' => $aggregate->document->documentDate,
            'due_date' => $aggregate->document->dueDate,
            'subtotal' => $aggregate->document->subtotal,
            'discount_total' => $aggregate->document->discountTotal,
            'tax_total' => $aggregate->document->taxTotal,
            'grand_total' => $aggregate->document->grandTotal,
            'data' => $aggregate->document->data,
            'notes' => $aggregate->document->notes,
            'attachments' => $aggregate->document->attachments,
            'items' => $items,
            'sales_link' => [
                'source_type' => $headerLink->get('source_type'),
                'source_id' => $headerLink->get('source_id'),
                'linked_amount' => $headerLink->get('linked_amount'),
                'linked_at' => $headerLink->get('linked_at'),
                'status' => $headerLink->get('status'),
            ],
        ];
    }
}
