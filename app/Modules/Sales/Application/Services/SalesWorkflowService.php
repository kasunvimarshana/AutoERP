<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Document\Application\DTOs\CreateDocumentDTO;
use Modules\Document\Application\Services\DocumentOrchestrator;
use Modules\Finance\Application\Contracts\Services\FinancePostingServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\CreateStockMovementServiceInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentAllocationServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PriceResolverServiceInterface;
use Modules\Sales\Application\Contracts\Services\SalesWorkflowServiceInterface;
use Modules\Sales\Application\Repositories\GdnHeaderRepositoryInterface;
use Modules\Sales\Application\Repositories\GdnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesDocumentLinkRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesPaymentAllocationRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesSettingRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesStatusHistoryRepositoryInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Modules\UOM\Application\Contracts\Services\UomConversionServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Throwable;

final class SalesWorkflowService implements SalesWorkflowServiceInterface
{
    private const ENTITY_TYPES = ['sales_order', 'gdn_header', 'sales_return'];

    /** @var array<string, list<string>> */
    private const ALLOWED_STATUS_TRANSITIONS = [
        'sales_order' => [
            'draft',
            'submitted',
            'approved',
            'confirmed',
            'partially_delivered',
            'delivered',
            'partially_documented',
            'documented',
            'closed',
            'cancelled',
            'reversed',
        ],
        'gdn_header' => [
            'draft',
            'submitted',
            'inspected',
            'confirmed',
            'posted',
            'partially_documented',
            'documented',
            'cancelled',
            'reversed',
        ],
        'sales_return' => [
            'draft',
            'submitted',
            'approved',
            'posted',
            'refunded',
            'closed',
            'cancelled',
            'reversed',
        ],
    ];

    /** @var array<string, array<string, list<string>>> */
    private const STATUS_TRANSITION_MATRIX = [
        'sales_order' => [
            'draft' => ['submitted', 'cancelled'],
            'submitted' => ['approved', 'cancelled'],
            'approved' => ['confirmed', 'cancelled'],
            'confirmed' => ['partially_delivered', 'delivered', 'partially_documented', 'documented', 'cancelled'],
            'partially_delivered' => ['delivered', 'partially_documented', 'documented', 'cancelled'],
            'delivered' => ['partially_documented', 'documented', 'closed', 'cancelled'],
            'partially_documented' => ['documented', 'closed', 'cancelled'],
            'documented' => ['closed', 'reversed'],
            'closed' => ['reversed'],
            'cancelled' => ['reversed'],
            'reversed' => [],
        ],
        'gdn_header' => [
            'draft' => ['submitted', 'cancelled'],
            'submitted' => ['inspected', 'confirmed', 'cancelled'],
            'inspected' => ['confirmed', 'cancelled'],
            'confirmed' => ['posted', 'cancelled'],
            'posted' => ['partially_documented', 'documented', 'reversed'],
            'partially_documented' => ['documented', 'reversed'],
            'documented' => ['reversed'],
            'cancelled' => ['reversed'],
            'reversed' => [],
        ],
        'sales_return' => [
            'draft' => ['submitted', 'cancelled'],
            'submitted' => ['approved', 'cancelled'],
            'approved' => ['posted', 'cancelled'],
            'posted' => ['refunded', 'closed', 'reversed'],
            'refunded' => ['closed', 'reversed'],
            'closed' => ['reversed'],
            'cancelled' => ['reversed'],
            'reversed' => [],
        ],
    ];

    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrderRepository,
        private readonly SalesOrderLineRepositoryInterface $salesOrderLineRepository,
        private readonly GdnHeaderRepositoryInterface $gdnHeaderRepository,
        private readonly GdnLineRepositoryInterface $gdnLineRepository,
        private readonly SalesReturnRepositoryInterface $salesReturnRepository,
        private readonly SalesReturnLineRepositoryInterface $salesReturnLineRepository,
        private readonly SalesDocumentLinkRepositoryInterface $salesDocumentLinkRepository,
        private readonly SalesPaymentAllocationRepositoryInterface $salesPaymentAllocationRepository,
        private readonly SalesSettingRepositoryInterface $salesSettingRepository,
        private readonly SalesStatusHistoryRepositoryInterface $salesStatusHistoryRepository,
        private readonly DocumentOrchestrator $documentOrchestrator,
        private readonly PaymentAllocationServiceInterface $paymentAllocationService,
        private readonly AdvancePaymentAllocationServiceInterface $advancePaymentAllocationService,
        private readonly CreateStockMovementServiceInterface $createStockMovementService,
        private readonly FinancePostingServiceInterface $financePostingService,
        private readonly PriceResolverServiceInterface $priceResolverService,
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UnitOfMeasureRepositoryInterface $unitOfMeasureRepository,
        private readonly UomConversionServiceInterface $uomConversionService,
    ) {}

    public function transition(string $entityType, int|string $id, array $payload): Result
    {
        try {
            if (! in_array($entityType, self::ENTITY_TYPES, true)) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Unsupported entity_type.'));
            }

            $status = strtolower(trim((string) ($payload['status'] ?? '')));
            if ($status === '') {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'status is required.'));
            }

            if (! in_array($status, self::ALLOWED_STATUS_TRANSITIONS[$entityType], true)) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Invalid status for entity type.'));
            }

            $record = $this->findEntity($entityType, $id);
            if (! $record instanceof DataRecord) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales entity not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if (! $this->isTenantMatch($record, $tenantId)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Cross-tenant transition is not allowed.',
                ));
            }

            $idempotencyKey = $this->normalizeIdempotencyKey($payload['idempotency_key'] ?? null);
            $idempotencySignature = $idempotencyKey !== ''
                ? $this->buildIdempotencySignature('transition', $payload)
                : '';
            if ($idempotencyKey !== '') {
                $replay = $this->findIdempotentActionHistory(
                    $tenantId,
                    $entityType,
                    (int) $record->id(),
                    $idempotencyKey,
                    'transition',
                );
                if ($replay instanceof DataRecord) {
                    $replayTargetStatus = strtolower((string) $replay->get('to_status', ''));
                    if ($replayTargetStatus !== $status) {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'idempotency_key is already used with a different request payload.',
                        ));
                    }

                    if ($this->hasIdempotencySignatureConflict($replay->get('metadata', []), $idempotencySignature)) {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'idempotency_key is already used with a different request payload.',
                        ));
                    }

                    return Result::success($record);
                }
            }

            if ((string) $record->get('status', '') === 'reversed') {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Reversed records are immutable.'));
            }

            $currentStatus = strtolower((string) $record->get('status', ''));
            if (! $this->isAllowedStatusTransition($entityType, $currentStatus, $status)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Transition is not allowed for current status.',
                ));
            }

            if (
                ($status === 'cancelled' || $status === 'reversed')
                && ! $this->canFinalizeEntity($entityType, (int) $record->id(), $tenantId)
            ) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Entity has active document/payment dependencies; finalize them first.',
                ));
            }

            if (
                ($status === 'cancelled' || $status === 'reversed')
                && $this->hasUnfinalizedDependentEntities($entityType, (int) $record->id(), $tenantId)
            ) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Entity has downstream Sales dependencies that must be finalized first.',
                ));
            }

            if ($status === 'reversed' && trim((string) ($payload['reason'] ?? '')) === '') {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'reason is required when reversing.',
                ));
            }

            if ($status === 'reversed') {
                if (
                    $this->requiresFinanceReversalAcknowledgement($entityType, $currentStatus)
                    && ($payload['finance_reversed'] ?? false) !== true
                ) {
                    return Result::failure(new Error(
                        SalesErrorCode::INVALID_VALUE,
                        'finance_reversed=true is required before status reversal.',
                    ));
                }

                if (
                    $this->requiresInventoryReversalAcknowledgement($entityType, $currentStatus)
                    && ($payload['inventory_reversed'] ?? false) !== true
                ) {
                    return Result::failure(new Error(
                        SalesErrorCode::INVALID_VALUE,
                        'inventory_reversed=true is required before status reversal.',
                    ));
                }
            }

            $actorId = isset($payload['actor_id']) ? (int) $payload['actor_id'] : null;

            return $this->withinEntityTransaction($entityType, function () use (
                $entityType,
                $id,
                $status,
                $actorId,
                $tenantId,
                $record,
                $payload,
                $idempotencyKey,
                $idempotencySignature,
            ): Result {
                $fields = [
                    'status' => $status,
                    'updated_by' => $actorId,
                ];

                if ($status === 'submitted') {
                    $fields['submitted_by'] = $actorId;
                    $fields['submitted_at'] = now()->toDateTimeString();
                }

                if ($status === 'approved') {
                    $fields['approved_by'] = $actorId;
                    $fields['approved_at'] = now()->toDateTimeString();
                }

                if ($status === 'confirmed') {
                    $fields['confirmed_by'] = $actorId;
                    $fields['confirmed_at'] = now()->toDateTimeString();
                }

                if ($status === 'posted') {
                    $fields['posted_by'] = $actorId;
                    $fields['posted_at'] = now()->toDateTimeString();
                }

                if ($status === 'cancelled') {
                    $fields['cancelled_by'] = $actorId;
                    $fields['cancelled_at'] = now()->toDateTimeString();
                }

                if ($status === 'reversed') {
                    $fields['reversed_by'] = $actorId;
                    $fields['reversed_at'] = now()->toDateTimeString();
                    if ($this->supportsDocumentStatus($entityType)) {
                        $fields['document_status'] = 'reversed';
                    }
                }

                $updated = $this->updateEntity($entityType, $id, $fields);

                $metadata = $this->metadataWithIdempotencyKey($payload, $idempotencyKey, $idempotencySignature);
                $metadata['workflow_action'] = 'transition';
                $metadata['target_status'] = $status;

                $this->salesStatusHistoryRepository->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $record->get('organization_unit_id'),
                    'metadata' => $metadata,
                    'entity_type' => $entityType,
                    'entity_id' => (int) $record->id(),
                    'from_status' => (string) $record->get('status', ''),
                    'to_status' => $status,
                    'reason' => $payload['reason'] ?? null,
                    'changed_by' => $actorId,
                    'changed_at' => now()->toDateTimeString(),
                ]);

                return Result::success($updated);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function createDocument(string $entityType, int|string $id, array $payload): Result
    {
        try {
            if (! in_array($entityType, self::ENTITY_TYPES, true)) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Unsupported entity_type.'));
            }

            $record = $this->findEntity($entityType, $id);
            if (! $record instanceof DataRecord) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales entity not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if (! $this->isTenantMatch($record, $tenantId)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Cross-tenant document creation is not allowed.',
                ));
            }

            $settings = $this->resolveActiveSettings($tenantId, $record->get('organization_unit_id') !== null
                ? (int) $record->get('organization_unit_id')
                : null);

            if (
                $entityType === 'sales_order'
                && $settings instanceof DataRecord
                && $settings->get('allow_direct_sales_invoice', true) === false
                && ! $this->hasEligibleGdnForSalesOrder((int) $record->id(), $tenantId)
            ) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Sales settings require GDN before direct sales invoice creation.',
                ));
            }

            if (in_array(strtolower((string) $record->get('status', '')), ['cancelled', 'reversed'], true)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Cannot create documents for cancelled/reversed entities.',
                ));
            }

            $idempotencyKey = $this->normalizeIdempotencyKey($payload['idempotency_key'] ?? null);
            $idempotencySignature = $idempotencyKey !== ''
                ? $this->buildIdempotencySignature('create_document', $payload)
                : '';
            if ($idempotencyKey !== '') {
                $idempotentDocument = $this->findIdempotentDocumentLink(
                    $tenantId,
                    $entityType,
                    (int) $record->id(),
                    $idempotencyKey,
                );
                if ($idempotentDocument instanceof DataRecord) {
                    if (
                        $this->hasIdempotencySignatureConflict(
                            $idempotentDocument->get('metadata', []),
                            $idempotencySignature,
                        )
                    ) {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'idempotency_key is already used with a different request payload.',
                        ));
                    }

                    return Result::success([
                        'source_type' => $entityType,
                        'source_id' => (int) $record->id(),
                        'document_id' => (int) $idempotentDocument->get('document_id', 0),
                        'document_number' => $this->resolveDocumentNumber(
                            $tenantId,
                            (int) $idempotentDocument->get('document_id', 0),
                        ),
                        'idempotent_replay' => true,
                    ]);
                }
            }

            $documentTypeId = isset($payload['document_type_id']) ? (int) $payload['document_type_id'] : 0;
            if ($documentTypeId < 1 && $settings instanceof DataRecord) {
                $documentTypeId = $this->resolveDefaultDocumentTypeIdFromSettings($entityType, $settings, $tenantId);
            }

            if ($documentTypeId < 1) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'document_type_id is required.'));
            }

            return $this->withinEntityTransaction($entityType, function () use (
                $entityType,
                $id,
                $payload,
                $record,
                $tenantId,
                $documentTypeId,
                $idempotencyKey,
                $idempotencySignature,
            ): Result {
                $items = $this->resolveDocumentItems($entityType, (int) $record->id(), $payload, $record);
                if ($items === []) {
                    return Result::failure(new Error(
                        SalesErrorCode::INVALID_VALUE,
                        'At least one document item is required.',
                    ));
                }

                $quantityValidation = $this->validateDocumentQuantities(
                    $entityType,
                    (int) $record->id(),
                    $tenantId,
                    $items,
                );
                if ($quantityValidation->isFailure()) {
                    return $quantityValidation;
                }

                $documentDate = (string) ($payload['document_date'] ?? now()->toDateString());
                $dto = new CreateDocumentDTO(
                    tenantId: $tenantId,
                    documentTypeId: $documentTypeId,
                    documentDate: $documentDate,
                    organizationUnitId: $record->get('organization_unit_id') !== null
                        ? (int) $record->get('organization_unit_id')
                        : null,
                    ownerId: isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
                    partyId: $record->get('customer_id') !== null ? (int) $record->get('customer_id') : null,
                    dueDate: isset($payload['due_date']) ? (string) $payload['due_date'] : null,
                    notes: isset($payload['notes']) ? (string) $payload['notes'] : null,
                    data: [
                        'source_type' => $entityType,
                        'source_id' => (int) $record->id(),
                        'sales_reference' => $record->get('reference'),
                        'sales_number' => $record->get('so_number')
                            ?? $record->get('gdn_number')
                            ?? $record->get('return_number'),
                    ],
                    items: $items,
                );

                $documentAggregate = $this->documentOrchestrator->create($dto);
                $documentId = $documentAggregate->document->id;
                if (! is_int($documentId) || $documentId < 1) {
                    return Result::failure(new Error(
                        SalesErrorCode::INVALID_VALUE,
                        'Document creation did not return a valid document id.',
                    ));
                }

                $this->salesDocumentLinkRepository->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $record->get('organization_unit_id'),
                    'metadata' => $this->metadataWithIdempotencyKey(
                        $payload,
                        $idempotencyKey,
                        $idempotencySignature,
                    ),
                    'source_type' => $entityType,
                    'source_id' => (int) $record->id(),
                    'source_line_id' => null,
                    'document_id' => $documentId,
                    'document_line_id' => null,
                    'linked_quantity' => null,
                    'linked_amount' => (float) ($record->get('grand_total') ?? 0),
                    'status' => 'active',
                    'linked_at' => now()->toDateTimeString(),
                    'created_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
                ]);

                foreach ($documentAggregate->items as $documentItem) {
                    $sourceLineId = (int) ($documentItem->data['source_line_id'] ?? 0);
                    if ($sourceLineId < 1) {
                        continue;
                    }

                    $this->salesDocumentLinkRepository->create([
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $record->get('organization_unit_id'),
                        'metadata' => $this->metadataWithIdempotencyKey(
                            $payload,
                            $idempotencyKey,
                            $idempotencySignature,
                        ),
                        'source_type' => $entityType,
                        'source_id' => (int) $record->id(),
                        'source_line_id' => $sourceLineId,
                        'document_id' => $documentId,
                        'document_line_id' => $documentItem->id,
                        'linked_quantity' => isset($documentItem->data['quantity'])
                            ? round((float) $documentItem->data['quantity'], 4)
                            : null,
                        'linked_amount' => round((float) $documentItem->lineTotal, 4),
                        'status' => 'active',
                        'linked_at' => now()->toDateTimeString(),
                        'created_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
                    ]);
                }

                $nextDocumentStatus = $this->supportsDocumentStatus($entityType) ? 'documented' : null;
                if ($nextDocumentStatus !== null) {
                    $this->updateEntity($entityType, $id, [
                        'document_status' => $nextDocumentStatus,
                        'updated_by' => $payload['actor_id'] ?? null,
                    ]);
                }

                return Result::success([
                    'source_type' => $entityType,
                    'source_id' => (int) $record->id(),
                    'document_id' => $documentId,
                    'document_number' => $documentAggregate->document->documentNumber,
                ]);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function allocatePayment(string $entityType, int|string $id, array $payload): Result
    {
        try {
            if (! in_array($entityType, self::ENTITY_TYPES, true)) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Unsupported entity_type.'));
            }

            $record = $this->findEntity($entityType, $id);
            if (! $record instanceof DataRecord) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales entity not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if (! $this->isTenantMatch($record, $tenantId)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Cross-tenant payment allocation is not allowed.',
                ));
            }

            if (in_array(strtolower((string) $record->get('status', '')), ['cancelled', 'reversed'], true)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Cannot allocate payments for cancelled/reversed entities.',
                ));
            }

            $documentId = isset($payload['document_id']) ? (int) $payload['document_id'] : 0;
            if ($documentId < 1) {
                $documentId = $this->resolveLatestDocumentId($entityType, (int) $record->id(), $tenantId);
            }
            if ($documentId < 1) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'A linked document is required before allocating payment.',
                ));
            }

            $allocatedAmount = round((float) ($payload['allocated_amount'] ?? 0), 4);
            if ($allocatedAmount <= 0) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'allocated_amount must be greater than zero.',
                ));
            }

            $hasPaymentId = isset($payload['payment_id']);
            $hasAdvancePaymentId = isset($payload['advance_payment_id']);
            if ($hasPaymentId && $hasAdvancePaymentId) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Use either payment_id or advance_payment_id, not both.',
                ));
            }

            $idempotencyKey = $this->normalizeIdempotencyKey($payload['idempotency_key'] ?? null);
            $idempotencySignature = $idempotencyKey !== ''
                ? $this->buildIdempotencySignature('allocate_payment', $payload)
                : '';
            if ($idempotencyKey !== '') {
                $idempotentAllocation = $this->findIdempotentPaymentAllocation($tenantId, $documentId, $idempotencyKey);
                if ($idempotentAllocation instanceof DataRecord) {
                    if (
                        $this->hasIdempotencySignatureConflict(
                            $idempotentAllocation->get('metadata', []),
                            $idempotencySignature,
                        )
                    ) {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'idempotency_key is already used with a different request payload.',
                        ));
                    }

                    return Result::success([
                        'allocation_id' => (int) $idempotentAllocation->id(),
                        'document_id' => $documentId,
                        'idempotent_replay' => true,
                    ]);
                }
            }

            $maxAllocatableAmount = $this->resolveMaxAllocatableAmount($tenantId, $documentId);
            if ($maxAllocatableAmount > 0) {
                $existingAllocations = $this->salesPaymentAllocationRepository->list([
                    'tenant_id' => $tenantId,
                    'document_id' => $documentId,
                    'status' => 'active',
                ]);

                $allocatedSoFar = 0.0;
                foreach ($existingAllocations as $allocation) {
                    if (! $allocation instanceof DataRecord) {
                        continue;
                    }

                    $allocatedSoFar += (float) $allocation->get('allocated_amount', 0);
                }

                if (($allocatedSoFar + $allocatedAmount) - $maxAllocatableAmount > 0.0001) {
                    return Result::failure(new Error(
                        SalesErrorCode::INVALID_VALUE,
                        'Allocation exceeds document allocatable amount.',
                    ));
                }
            }

            return $this->withinEntityTransaction($entityType, function () use (
                $payload,
                $tenantId,
                $record,
                $entityType,
                $documentId,
                $allocatedAmount,
                $idempotencyKey,
                $idempotencySignature,
            ): Result {
                if (isset($payload['payment_id'])) {
                    $allocationResult = $this->paymentAllocationService->createAllocation([
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $record->get('organization_unit_id'),
                        'payment_id' => (int) $payload['payment_id'],
                        'document_type' => $entityType,
                        'document_id' => $documentId,
                        'allocated_amount' => $allocatedAmount,
                        'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                        'reference' => $payload['reference'] ?? null,
                    ]);
                } elseif (isset($payload['advance_payment_id'])) {
                    $allocationResult = $this->advancePaymentAllocationService->createAllocation([
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $record->get('organization_unit_id'),
                        'advance_payment_id' => (int) $payload['advance_payment_id'],
                        'document_type' => $entityType,
                        'document_id' => $documentId,
                        'allocated_amount' => $allocatedAmount,
                        'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                        'reference' => $payload['reference'] ?? null,
                    ]);
                } else {
                    return Result::failure(new Error(
                        SalesErrorCode::INVALID_VALUE,
                        'payment_id or advance_payment_id is required.',
                    ));
                }

                if ($allocationResult->isFailure()) {
                    return $allocationResult;
                }

                $this->salesPaymentAllocationRepository->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $record->get('organization_unit_id'),
                    'metadata' => $this->metadataWithIdempotencyKey($payload, $idempotencyKey, $idempotencySignature),
                    'document_id' => $documentId,
                    'payment_id' => isset($payload['payment_id']) ? (int) $payload['payment_id'] : null,
                    'advance_payment_id' => isset($payload['advance_payment_id'])
                        ? (int) $payload['advance_payment_id']
                        : null,
                    'allocated_amount' => $allocatedAmount,
                    'currency_id' => isset($payload['currency_id']) ? (int) $payload['currency_id'] : null,
                    'base_allocated_amount' => isset($payload['base_allocated_amount'])
                        ? round((float) $payload['base_allocated_amount'], 4)
                        : $allocatedAmount,
                    'status' => 'active',
                    'allocated_at' => now()->toDateTimeString(),
                    'created_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
                ]);

                return Result::success($allocationResult->valueOrFail());
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function postInventory(string $entityType, int|string $id, array $payload): Result
    {
        try {
            if (! in_array($entityType, ['gdn_header', 'sales_return'], true)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Inventory posting is supported only for GDN and Sales Return.',
                ));
            }

            $record = $this->findEntity($entityType, $id);
            if (! $record instanceof DataRecord) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales entity not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if (! $this->isTenantMatch($record, $tenantId)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Cross-tenant inventory posting is not allowed.',
                ));
            }

            if (in_array(strtolower((string) $record->get('status', '')), ['cancelled', 'reversed'], true)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Cannot post inventory for cancelled/reversed entities.',
                ));
            }

            $idempotencyKey = $this->normalizeIdempotencyKey($payload['idempotency_key'] ?? null);
            $idempotencySignature = $idempotencyKey !== ''
                ? $this->buildIdempotencySignature('inventory_post', $payload)
                : '';
            if ($idempotencyKey !== '') {
                $replay = $this->findIdempotentActionHistory(
                    $tenantId,
                    $entityType,
                    (int) $record->id(),
                    $idempotencyKey,
                    'inventory_post',
                );
                if ($replay instanceof DataRecord) {
                    if ($this->hasIdempotencySignatureConflict($replay->get('metadata', []), $idempotencySignature)) {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'idempotency_key is already used with a different request payload.',
                        ));
                    }

                    return Result::success([
                        'posted' => true,
                        'idempotent_replay' => true,
                    ]);
                }
            }

            $lineRecords = $this->resolveLines($entityType, (int) $record->id());
            if ($lineRecords === []) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'No lines found for inventory posting.',
                ));
            }

            return $this->withinEntityTransaction($entityType, function () use (
                $lineRecords,
                $entityType,
                $tenantId,
                $record,
                $payload,
                $id,
                $idempotencyKey,
                $idempotencySignature,
            ): Result {
                foreach ($lineRecords as $line) {
                    $quantity = $this->resolveInventoryPostingQuantity($entityType, $line);
                    if ($quantity <= 0) {
                        continue;
                    }

                    $itemId = (int) $line->get('item_id', 0);
                    if (! $this->itemRepository->findByIdInTenant($itemId, $tenantId) instanceof DataRecord) {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'Inventory posting failed because item is not available in tenant scope.',
                        ));
                    }

                    $transactionUomId = (int) $line->get('uom_id', 0);
                    $transactionUom = $this->unitOfMeasureRepository->findById($transactionUomId);
                    if (! $transactionUom instanceof DataRecord) {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'Inventory posting failed because transaction UOM is invalid.',
                        ));
                    }

                    $baseUomResult = $this->uomConversionService->getBaseUnit(
                        (string) $transactionUom->get('type', ''),
                        $tenantId,
                    );
                    if ($baseUomResult->isFailure()) {
                        return $baseUomResult;
                    }

                    $baseUom = $baseUomResult->valueOrFail();
                    $baseQuantityResult = $this->uomConversionService->normalizeToBase(
                        $quantity,
                        $transactionUomId,
                        $tenantId,
                    );
                    if ($baseQuantityResult->isFailure()) {
                        return $baseQuantityResult;
                    }

                    $baseQuantity = (float) $baseQuantityResult->valueOrFail();
                    // Sales delivery issues stock; only restocked return quantity comes back into inventory.
                    $direction = $entityType === 'sales_return' ? 'IN' : 'OUT';
                    $movementType = $entityType === 'sales_return' ? 'SALES_RETURN' : 'SALES_GDN';

                    $movementResult = $this->createStockMovementService->execute([
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $record->get('organization_unit_id'),
                        'direction' => $direction,
                        'movement_type' => $movementType,
                        'item_id' => $itemId,
                        'variant_id' => $line->get('variant_id'),
                        'batch_id' => $line->get('batch_id'),
                        'serial_id' => $line->get('serial_id'),
                        'location_id' => $line->get('location_id'),
                        'warehouse_id' => $line->get('warehouse_id') ?? $record->get('warehouse_id'),
                        'source_type' => $entityType,
                        'source_id' => (int) $record->id(),
                        'source_line_id' => (int) $line->id(),
                        'transaction_uom_id' => $transactionUomId,
                        'base_uom_id' => (int) $baseUom->id(),
                        'quantity' => $quantity,
                        'base_quantity' => $baseQuantity,
                        'quantity_in' => $direction === 'IN' ? $quantity : 0,
                        'quantity_out' => $direction === 'OUT' ? $quantity : 0,
                        'base_quantity_in' => $direction === 'IN' ? $baseQuantity : 0,
                        'base_quantity_out' => $direction === 'OUT' ? $baseQuantity : 0,
                        'unit_cost' => (float) $line->get('unit_price', 0),
                        'total_cost' => round($quantity * (float) $line->get('unit_price', 0), 4),
                        'status' => 'POSTED',
                        'performed_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
                        'performed_at' => now()->toDateTimeString(),
                        'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                    ]);

                    if ($movementResult->isFailure()) {
                        return $movementResult;
                    }
                }

                if ($entityType === 'gdn_header') {
                    $this->updateEntity($entityType, $id, [
                        'status' => 'posted',
                        'posted_by' => $payload['actor_id'] ?? null,
                        'posted_at' => now()->toDateTimeString(),
                        'updated_by' => $payload['actor_id'] ?? null,
                    ]);
                }

                $fromStatus = strtolower((string) $record->get('status', ''));
                $toStatus = $entityType === 'gdn_header' ? 'posted' : $fromStatus;
                $metadata = $this->metadataWithIdempotencyKey($payload, $idempotencyKey, $idempotencySignature);
                $metadata['workflow_action'] = 'inventory_post';

                $this->salesStatusHistoryRepository->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $record->get('organization_unit_id'),
                    'metadata' => $metadata,
                    'entity_type' => $entityType,
                    'entity_id' => (int) $record->id(),
                    'from_status' => $fromStatus,
                    'to_status' => $toStatus,
                    'reason' => 'inventory_post',
                    'changed_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
                    'changed_at' => now()->toDateTimeString(),
                ]);

                return Result::success(['posted' => true]);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function postFinance(string $entityType, int|string $id, array $payload): Result
    {
        try {
            if (! in_array($entityType, self::ENTITY_TYPES, true)) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Unsupported entity_type.'));
            }

            $record = $this->findEntity($entityType, $id);
            if (! $record instanceof DataRecord) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales entity not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if (! $this->isTenantMatch($record, $tenantId)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Cross-tenant finance posting is not allowed.',
                ));
            }

            if (in_array(strtolower((string) $record->get('status', '')), ['cancelled', 'reversed'], true)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Cannot post finance for cancelled/reversed entities.',
                ));
            }

            $entryPayload = is_array($payload['entry_payload'] ?? null) ? $payload['entry_payload'] : [];
            $linesPayload = is_array($payload['lines_payload'] ?? null) ? $payload['lines_payload'] : [];
            if ($entryPayload === [] || $linesPayload === []) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'entry_payload and lines_payload are required.',
                ));
            }

            $idempotencyKey = $this->normalizeIdempotencyKey($payload['idempotency_key'] ?? null);
            $idempotencySignature = $idempotencyKey !== ''
                ? $this->buildIdempotencySignature('finance_post', $payload)
                : '';
            if ($idempotencyKey !== '') {
                $replay = $this->findIdempotentActionHistory(
                    $tenantId,
                    $entityType,
                    (int) $record->id(),
                    $idempotencyKey,
                    'finance_post',
                );
                if ($replay instanceof DataRecord) {
                    if ($this->hasIdempotencySignatureConflict($replay->get('metadata', []), $idempotencySignature)) {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'idempotency_key is already used with a different request payload.',
                        ));
                    }

                    return Result::success([
                        'posted' => true,
                        'idempotent_replay' => true,
                    ]);
                }
            }

            $entryPayload['tenant_id'] = $tenantId;
            $entryPayload['organization_unit_id'] = $entryPayload['organization_unit_id']
                ?? $record->get('organization_unit_id');
            $entryPayload['source_type'] = $entryPayload['source_type'] ?? $entityType;
            $entryPayload['source_id'] = $entryPayload['source_id'] ?? (int) $record->id();

            return $this->withinEntityTransaction($entityType, function () use (
                $entryPayload,
                $linesPayload,
                $payload,
                $record,
                $entityType,
                $tenantId,
                $idempotencyKey,
                $idempotencySignature,
            ): Result {
                $postingResult = $this->financePostingService->postFromSource($entryPayload, $linesPayload);
                if ($postingResult->isFailure()) {
                    return $postingResult;
                }

                $status = strtolower((string) $record->get('status', ''));
                $metadata = $this->metadataWithIdempotencyKey($payload, $idempotencyKey, $idempotencySignature);
                $metadata['workflow_action'] = 'finance_post';

                $this->salesStatusHistoryRepository->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $record->get('organization_unit_id'),
                    'metadata' => $metadata,
                    'entity_type' => $entityType,
                    'entity_id' => (int) $record->id(),
                    'from_status' => $status,
                    'to_status' => $status,
                    'reason' => 'finance_post',
                    'changed_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
                    'changed_at' => now()->toDateTimeString(),
                ]);

                return Result::success($postingResult->valueOrFail());
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function reverseFinance(string $entityType, int|string $id, array $payload): Result
    {
        try {
            if (! in_array($entityType, self::ENTITY_TYPES, true)) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Unsupported entity_type.'));
            }

            $record = $this->findEntity($entityType, $id);
            if (! $record instanceof DataRecord) {
                return Result::failure(new Error(SalesErrorCode::NOT_FOUND, 'Sales entity not found.'));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if (! $this->isTenantMatch($record, $tenantId)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Cross-tenant finance reversal is not allowed.',
                ));
            }

            if (strtolower((string) $record->get('status', '')) === 'reversed') {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Entity is already reversed.',
                ));
            }

            $journalEntryId = $payload['journal_entry_id'] ?? null;
            if (! is_int($journalEntryId) && ! is_string($journalEntryId)) {
                return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'journal_entry_id is required.'));
            }

            $idempotencyKey = $this->normalizeIdempotencyKey($payload['idempotency_key'] ?? null);
            $idempotencySignature = $idempotencyKey !== ''
                ? $this->buildIdempotencySignature('finance_reverse', $payload)
                : '';
            if ($idempotencyKey !== '') {
                $replay = $this->findIdempotentActionHistory(
                    $tenantId,
                    $entityType,
                    (int) $record->id(),
                    $idempotencyKey,
                    'finance_reverse',
                );

                if ($replay instanceof DataRecord) {
                    if ($this->hasIdempotencySignatureConflict($replay->get('metadata', []), $idempotencySignature)) {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'idempotency_key is already used with a different request payload.',
                        ));
                    }

                    $metadata = $replay->get('metadata', []);
                    $replayedJournalEntryId = is_array($metadata) ? ($metadata['journal_entry_id'] ?? null) : null;
                    if ($replayedJournalEntryId === null || trim((string) $replayedJournalEntryId) === '') {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'idempotency_key cannot be safely replayed due to missing finance reversal metadata.',
                        ));
                    }

                    if (
                        $replayedJournalEntryId !== null
                        && (string) $replayedJournalEntryId !== (string) $journalEntryId
                    ) {
                        return Result::failure(new Error(
                            SalesErrorCode::INVALID_VALUE,
                            'idempotency_key is already used with a different request payload.',
                        ));
                    }

                    if ((string) $replayedJournalEntryId === (string) $journalEntryId) {
                        return Result::success([
                            'reversed' => true,
                            'idempotent_replay' => true,
                        ]);
                    }
                }
            }

            return $this->withinEntityTransaction($entityType, function () use (
                $journalEntryId,
                $tenantId,
                $record,
                $payload,
                $entityType,
                $idempotencyKey,
                $idempotencySignature,
            ): Result {
                $reversalResult = $this->financePostingService->reverseByEntryId($journalEntryId, [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $record->get('organization_unit_id'),
                    'reason' => $payload['reason'] ?? null,
                    'reversed_by' => $payload['actor_id'] ?? null,
                ]);

                if ($reversalResult->isFailure()) {
                    return $reversalResult;
                }

                $status = strtolower((string) $record->get('status', ''));
                $metadata = $this->metadataWithIdempotencyKey($payload, $idempotencyKey, $idempotencySignature);
                $metadata['workflow_action'] = 'finance_reverse';
                $metadata['journal_entry_id'] = (string) $journalEntryId;

                $this->salesStatusHistoryRepository->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $record->get('organization_unit_id'),
                    'metadata' => $metadata,
                    'entity_type' => $entityType,
                    'entity_id' => (int) $record->id(),
                    'from_status' => $status,
                    'to_status' => $status,
                    'reason' => 'finance_reverse',
                    'changed_by' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
                    'changed_at' => now()->toDateTimeString(),
                ]);

                return $reversalResult;
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(SalesErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function findEntity(string $entityType, int|string $id): ?DataRecord
    {
        return match ($entityType) {
            'sales_order' => $this->salesOrderRepository->findById($id),
            'gdn_header' => $this->gdnHeaderRepository->findById($id),
            'sales_return' => $this->salesReturnRepository->findById($id),
            default => null,
        };
    }

    private function withinEntityTransaction(string $entityType, callable $callback): Result
    {
        return match ($entityType) {
            'sales_order' => $this->salesOrderRepository->transaction($callback),
            'gdn_header' => $this->gdnHeaderRepository->transaction($callback),
            'sales_return' => $this->salesReturnRepository->transaction($callback),
            default => Result::failure(new Error(SalesErrorCode::INVALID_VALUE, 'Unsupported entity type.')),
        };
    }

    private function updateEntity(string $entityType, int|string $id, array $payload): DataRecord
    {
        return match ($entityType) {
            'sales_order' => $this->salesOrderRepository->update($id, $payload),
            'gdn_header' => $this->gdnHeaderRepository->update($id, $payload),
            'sales_return' => $this->salesReturnRepository->update($id, $payload),
            default => throw new \InvalidArgumentException('Unsupported entity type.'),
        };
    }

    /** @return list<DataRecord> */
    private function resolveLines(string $entityType, int $entityId): array
    {
        return match ($entityType) {
            'sales_order' => $this->salesOrderLineRepository->list(['sales_order_id' => $entityId]),
            'gdn_header' => $this->gdnLineRepository->list(['gdn_header_id' => $entityId]),
            'sales_return' => $this->salesReturnLineRepository->list(['sales_return_id' => $entityId]),
            default => [],
        };
    }

    /** @return list<array<string, mixed>> */
    private function resolveDocumentItems(
        string $entityType,
        int $entityId,
        array $payload,
        ?DataRecord $header = null,
    ): array {
        $providedItems = $payload['items'] ?? null;
        if (is_array($providedItems) && $providedItems !== []) {
            return $providedItems;
        }

        $records = $this->resolveLines($entityType, $entityId);
        $items = [];

        foreach ($records as $line) {
            $qty = $this->resolveDocumentLineQuantity($entityType, $line);
            if ($qty <= 0) {
                continue;
            }

            $unitPrice = (float) $line->get('unit_price', 0);
            $lineTotal = (float) $line->get('line_total_with_tax', $line->get('line_total', 0));

            if (($unitPrice <= 0 || $lineTotal <= 0) && $header instanceof DataRecord) {
                $priceResult = $this->priceResolverService->resolvePrice([
                    'tenant_id' => (int) $header->get('tenant_id', 0),
                    'item_id' => (int) $line->get('item_id', 0),
                    'quantity' => $qty,
                    'uom_id' => (int) $line->get('uom_id', 0),
                    'party_type' => 'customer',
                    'party_id' => (int) $header->get('customer_id', 0),
                    'price_list_id' => $header->get('price_list_id') !== null
                        ? (int) $header->get('price_list_id')
                        : null,
                    'currency_id' => $header->get('currency_id') !== null
                        ? (int) $header->get('currency_id')
                        : null,
                    'source_type' => $entityType,
                    'source_id' => $entityId,
                    'date' => now()->toDateString(),
                ]);

                if ($priceResult->isSuccess()) {
                    $priceData = $priceResult->valueOrFail();
                    if ($priceData instanceof DataRecord) {
                        $lineTotal = (float) $priceData->get('final_amount', $lineTotal);
                        $resolvedUnitPrice = (float) $priceData->get('base_unit_price', $unitPrice);
                        if ($resolvedUnitPrice > 0) {
                            $unitPrice = $resolvedUnitPrice;
                        }
                    }
                }
            }

            if ($lineTotal <= 0) {
                $lineTotal = round($qty * max($unitPrice, 0), 4);
            }

            if ($lineTotal <= 0) {
                continue;
            }

            $items[] = [
                'item_type' => 'sales_line',
                'description' => $line->get('description'),
                'line_total' => $lineTotal,
                'data' => [
                    'source_line_id' => (int) $line->id(),
                    'item_id' => $line->get('item_id'),
                    'variant_id' => $line->get('variant_id'),
                    'uom_id' => $line->get('uom_id'),
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => (float) $line->get('discount_amount', 0),
                    'tax_amount' => (float) $line->get('tax_amount', 0),
                ],
            ];
        }

        return $items;
    }

    private function supportsDocumentStatus(string $entityType): bool
    {
        return in_array($entityType, ['sales_order', 'gdn_header'], true);
    }

    private function isTenantMatch(DataRecord $record, int $tenantId): bool
    {
        return $tenantId > 0 && $tenantId === (int) $record->get('tenant_id', 0);
    }

    private function resolveLatestDocumentId(string $entityType, int $sourceId, int $tenantId): int
    {
        $links = $this->salesDocumentLinkRepository->list([
            'tenant_id' => $tenantId,
            'source_type' => $entityType,
            'source_id' => $sourceId,
            'status' => 'active',
        ]);

        $latest = end($links);
        if (! $latest instanceof DataRecord) {
            return 0;
        }

        return (int) $latest->get('document_id', 0);
    }

    private function resolveMaxAllocatableAmount(int $tenantId, int $documentId): float
    {
        $links = $this->salesDocumentLinkRepository->list([
            'tenant_id' => $tenantId,
            'document_id' => $documentId,
            'status' => 'active',
        ]);

        $maxAmount = 0.0;
        foreach ($links as $link) {
            if (! $link instanceof DataRecord) {
                continue;
            }

            if ((int) $link->get('source_line_id', 0) > 0 || (int) $link->get('document_line_id', 0) > 0) {
                continue;
            }

            $maxAmount = max($maxAmount, (float) $link->get('linked_amount', 0));
        }

        return round($maxAmount, 4);
    }

    private function findIdempotentDocumentLink(
        int $tenantId,
        string $entityType,
        int $sourceId,
        string $idempotencyKey,
    ): ?DataRecord {
        $links = $this->salesDocumentLinkRepository->list([
            'tenant_id' => $tenantId,
            'source_type' => $entityType,
            'source_id' => $sourceId,
            'status' => 'active',
        ]);

        foreach ($links as $link) {
            if (! $link instanceof DataRecord) {
                continue;
            }

            if ((int) $link->get('source_line_id', 0) > 0 || (int) $link->get('document_line_id', 0) > 0) {
                continue;
            }

            $metadata = $link->get('metadata', []);
            if (! is_array($metadata)) {
                continue;
            }

            if ((string) ($metadata['idempotency_key'] ?? '') === $idempotencyKey) {
                return $link;
            }
        }

        return null;
    }

    private function findIdempotentPaymentAllocation(
        int $tenantId,
        int $documentId,
        string $idempotencyKey,
    ): ?DataRecord {
        $allocations = $this->salesPaymentAllocationRepository->list([
            'tenant_id' => $tenantId,
            'document_id' => $documentId,
            'status' => 'active',
        ]);

        foreach ($allocations as $allocation) {
            if (! $allocation instanceof DataRecord) {
                continue;
            }

            $metadata = $allocation->get('metadata', []);
            if (! is_array($metadata)) {
                continue;
            }

            if ((string) ($metadata['idempotency_key'] ?? '') === $idempotencyKey) {
                return $allocation;
            }
        }

        return null;
    }

    private function findIdempotentActionHistory(
        int $tenantId,
        string $entityType,
        int $entityId,
        string $idempotencyKey,
        string $action,
    ): ?DataRecord {
        $histories = $this->salesStatusHistoryRepository->list([
            'tenant_id' => $tenantId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        foreach ($histories as $history) {
            if (! $history instanceof DataRecord) {
                continue;
            }

            $metadata = $history->get('metadata', []);
            if (! is_array($metadata)) {
                continue;
            }

            if ((string) ($metadata['idempotency_key'] ?? '') !== $idempotencyKey) {
                continue;
            }

            if ((string) ($metadata['workflow_action'] ?? '') !== $action) {
                continue;
            }

            return $history;
        }

        return null;
    }

    private function normalizeIdempotencyKey(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function buildIdempotencySignature(string $action, array $payload): string
    {
        $normalized = $payload;
        unset($normalized['idempotency_key']);

        $canonical = [
            'action' => $action,
            'payload' => $this->normalizeValueForSignature($normalized),
        ];

        $json = json_encode($canonical, JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            return '';
        }

        return hash('sha256', $json);
    }

    private function normalizeValueForSignature(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            $normalizedList = [];
            foreach ($value as $item) {
                $normalizedList[] = $this->normalizeValueForSignature($item);
            }

            return $normalizedList;
        }

        $normalizedMap = [];
        foreach ($value as $key => $item) {
            $normalizedMap[(string) $key] = $this->normalizeValueForSignature($item);
        }
        ksort($normalizedMap);

        return $normalizedMap;
    }

    private function hasIdempotencySignatureConflict(mixed $metadataValue, string $requestSignature): bool
    {
        if (! is_array($metadataValue) || $requestSignature === '') {
            return false;
        }

        $storedSignature = (string) ($metadataValue['idempotency_signature'] ?? '');
        if ($storedSignature === '') {
            return false;
        }

        return ! hash_equals($storedSignature, $requestSignature);
    }

    /** @return array<string, mixed> */
    private function metadataWithIdempotencyKey(
        array $payload,
        string $idempotencyKey,
        string $idempotencySignature = '',
    ): array {
        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];
        if ($idempotencyKey !== '') {
            $metadata['idempotency_key'] = $idempotencyKey;
        }
        if ($idempotencySignature !== '') {
            $metadata['idempotency_signature'] = $idempotencySignature;
        }

        return $metadata;
    }

    private function resolveDocumentNumber(int $tenantId, int $documentId): ?string
    {
        if ($documentId < 1) {
            return null;
        }

        try {
            $aggregate = $this->documentOrchestrator->show($tenantId, $documentId);

            return $aggregate->document->documentNumber;
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveActiveSettings(int $tenantId, ?int $organizationUnitId): ?DataRecord
    {
        if ($organizationUnitId !== null && $organizationUnitId > 0) {
            $orgScopedSettings = $this->salesSettingRepository->list([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'is_active' => true,
            ]);

            $orgScoped = end($orgScopedSettings);
            if ($orgScoped instanceof DataRecord) {
                return $orgScoped;
            }
        }

        $tenantScopedSettings = $this->salesSettingRepository->list([
            'tenant_id' => $tenantId,
            'is_active' => true,
        ]);

        $tenantScoped = end($tenantScopedSettings);

        return $tenantScoped instanceof DataRecord ? $tenantScoped : null;
    }

    private function resolveDefaultDocumentTypeIdFromSettings(
        string $entityType,
        DataRecord $settings,
        int $tenantId,
    ): int {
        $definitionId = match ($entityType) {
            'sales_order' => (int) ($settings->get('sales_invoice_document_definition_id')
                ?? $settings->get('sales_order_document_definition_id')
                ?? 0),
            'gdn_header' => (int) ($settings->get('sales_invoice_document_definition_id')
                ?? $settings->get('gdn_document_definition_id')
                ?? 0),
            'sales_return' => (int) ($settings->get('sales_return_document_definition_id') ?? 0),
            default => 0,
        };

        if ($definitionId < 1) {
            return 0;
        }

        $definitions = $this->documentOrchestrator->listDocumentDefinitions($tenantId);
        foreach ($definitions as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            if ((int) ($definition['id'] ?? 0) !== $definitionId) {
                continue;
            }

            return (int) ($definition['document_type_id'] ?? 0);
        }

        return 0;
    }

    private function hasEligibleGdnForSalesOrder(int $salesOrderId, int $tenantId): bool
    {
        $gdns = $this->gdnHeaderRepository->list([
            'tenant_id' => $tenantId,
            'sales_order_id' => $salesOrderId,
        ]);

        foreach ($gdns as $gdn) {
            if (! $gdn instanceof DataRecord) {
                continue;
            }

            $status = strtolower((string) $gdn->get('status', ''));
            if (! in_array($status, ['draft', 'cancelled', 'reversed'], true)) {
                return true;
            }
        }

        return false;
    }

    private function isAllowedStatusTransition(string $entityType, string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        $nextStates = self::STATUS_TRANSITION_MATRIX[$entityType][$from] ?? null;
        if (! is_array($nextStates)) {
            return false;
        }

        return in_array($to, $nextStates, true);
    }

    private function canFinalizeEntity(string $entityType, int $sourceId, int $tenantId): bool
    {
        $activeLinks = $this->salesDocumentLinkRepository->list([
            'tenant_id' => $tenantId,
            'source_type' => $entityType,
            'source_id' => $sourceId,
            'status' => 'active',
        ]);

        if ($activeLinks === []) {
            return true;
        }

        $documentIds = [];
        foreach ($activeLinks as $link) {
            if (! $link instanceof DataRecord) {
                continue;
            }

            $documentId = (int) $link->get('document_id', 0);
            if ($documentId > 0) {
                $documentIds[] = $documentId;
            }
        }

        $documentIds = array_values(array_unique($documentIds));
        foreach ($documentIds as $documentId) {
            $activeAllocations = $this->salesPaymentAllocationRepository->list([
                'tenant_id' => $tenantId,
                'document_id' => $documentId,
                'status' => 'active',
            ]);

            if ($activeAllocations !== []) {
                return false;
            }
        }

        return true;
    }

    /** @param list<array<string, mixed>> $items */
    private function validateDocumentQuantities(
        string $entityType,
        int $sourceId,
        int $tenantId,
        array $items,
    ): Result {
        $lineRecords = $this->resolveLines($entityType, $sourceId);
        if ($lineRecords === []) {
            return Result::failure(new Error(
                SalesErrorCode::INVALID_VALUE,
                'No source lines found for document quantity validation.',
            ));
        }

        $availableByLineId = [];
        foreach ($lineRecords as $lineRecord) {
            if (! $lineRecord instanceof DataRecord) {
                continue;
            }

            $lineId = (int) $lineRecord->id();
            if ($lineId < 1) {
                continue;
            }

            $availableByLineId[$lineId] = round($this->resolveDocumentableQuantity($entityType, $lineRecord), 4);
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $sourceLineId = (int) ($item['data']['source_line_id'] ?? 0);
            if ($sourceLineId < 1) {
                continue;
            }

            if (! array_key_exists($sourceLineId, $availableByLineId)) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Document item references an unknown source line.',
                ));
            }

            $requestedQuantity = round((float) ($item['data']['quantity'] ?? 0), 4);
            if ($requestedQuantity <= 0) {
                continue;
            }

            $existingLinks = $this->salesDocumentLinkRepository->list([
                'tenant_id' => $tenantId,
                'source_type' => $entityType,
                'source_id' => $sourceId,
                'source_line_id' => $sourceLineId,
                'status' => 'active',
            ]);

            $alreadyLinkedQuantity = 0.0;
            foreach ($existingLinks as $existingLink) {
                if (! $existingLink instanceof DataRecord) {
                    continue;
                }

                $alreadyLinkedQuantity += (float) $existingLink->get('linked_quantity', 0);
            }

            $maxQuantity = (float) $availableByLineId[$sourceLineId];
            if (($alreadyLinkedQuantity + $requestedQuantity) - $maxQuantity > 0.0001) {
                return Result::failure(new Error(
                    SalesErrorCode::INVALID_VALUE,
                    'Document quantity exceeds available quantity for one or more source lines.',
                ));
            }
        }

        return Result::success(true);
    }

    private function resolveDocumentableQuantity(string $entityType, DataRecord $line): float
    {
        return match ($entityType) {
            'sales_order' => (float) $line->get('ordered_qty', 0),
            'gdn_header' => (float) $line->get('delivered_qty', 0),
            'sales_return' => (float) $line->get('return_qty', 0),
            default => 0.0,
        };
    }

    private function resolveInventoryPostingQuantity(string $entityType, DataRecord $line): float
    {
        if ($entityType === 'sales_return') {
            $restockQty = (float) $line->get('restock_qty', 0);

            return $restockQty > 0 ? $restockQty : (float) $line->get('return_qty', 0);
        }

        return (float) $line->get('delivered_qty', 0);
    }

    private function resolveDocumentLineQuantity(string $entityType, DataRecord $line): float
    {
        return match ($entityType) {
            'sales_order' => (float) $line->get('ordered_qty', 0),
            'gdn_header' => (float) $line->get('delivered_qty', 0),
            'sales_return' => (float) $line->get('return_qty', 0),
            default => 0.0,
        };
    }

    private function hasUnfinalizedDependentEntities(string $entityType, int $sourceId, int $tenantId): bool
    {
        if ($entityType === 'sales_order') {
            $dependentGdns = $this->gdnHeaderRepository->list([
                'tenant_id' => $tenantId,
                'sales_order_id' => $sourceId,
            ]);

            foreach ($dependentGdns as $gdn) {
                if (! $gdn instanceof DataRecord) {
                    continue;
                }

                if (! in_array(strtolower((string) $gdn->get('status', '')), ['cancelled', 'reversed'], true)) {
                    return true;
                }
            }

            $dependentReturns = $this->salesReturnRepository->list([
                'tenant_id' => $tenantId,
                'original_sales_order_id' => $sourceId,
            ]);

            foreach ($dependentReturns as $return) {
                if (! $return instanceof DataRecord) {
                    continue;
                }

                $returnStatus = strtolower((string) $return->get('status', ''));
                if (! in_array($returnStatus, ['closed', 'cancelled', 'reversed'], true)) {
                    return true;
                }
            }

            return false;
        }

        if ($entityType === 'gdn_header') {
            $dependentReturns = $this->salesReturnRepository->list([
                'tenant_id' => $tenantId,
                'original_gdn_id' => $sourceId,
            ]);

            foreach ($dependentReturns as $return) {
                if (! $return instanceof DataRecord) {
                    continue;
                }

                $returnStatus = strtolower((string) $return->get('status', ''));
                if (! in_array($returnStatus, ['closed', 'cancelled', 'reversed'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function requiresFinanceReversalAcknowledgement(string $entityType, string $currentStatus): bool
    {
        return match ($entityType) {
            'sales_order' => in_array($currentStatus, ['documented', 'closed'], true),
            'gdn_header' => in_array($currentStatus, ['posted', 'partially_documented', 'documented'], true),
            'sales_return' => in_array($currentStatus, ['posted', 'refunded', 'closed'], true),
            default => false,
        };
    }

    private function requiresInventoryReversalAcknowledgement(string $entityType, string $currentStatus): bool
    {
        return match ($entityType) {
            'gdn_header' => in_array($currentStatus, ['posted', 'partially_documented', 'documented'], true),
            'sales_return' => in_array($currentStatus, ['posted', 'refunded', 'closed'], true),
            default => false,
        };
    }
}
