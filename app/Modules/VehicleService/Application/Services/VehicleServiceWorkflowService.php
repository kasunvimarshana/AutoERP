<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Document\Application\DTOs\CreateDocumentDTO;
use Modules\Document\Application\Services\DocumentOrchestrator;
use Modules\Finance\Application\Contracts\Services\FinancePostingServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\CreateStockMovementServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentAllocationServiceInterface;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceWorkflowServiceInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobDocumentLinkRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobInventoryLinkRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobPaymentLinkRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobStatusHistoryRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceSettingRepositoryInterface;
use Modules\VehicleService\Domain\Constants\VehicleServiceErrorCode;
use Throwable;

final class VehicleServiceWorkflowService implements VehicleServiceWorkflowServiceInterface
{
    /** @var array<string, list<string>> */
    private const STATUS_TRANSITION_MATRIX = [
        'open' => ['inspection', 'diagnosis', 'in_progress', 'cancelled'],
        'inspection' => ['diagnosis', 'in_progress', 'cancelled'],
        'diagnosis' => ['waiting_approval', 'in_progress', 'cancelled'],
        'waiting_approval' => ['approved', 'cancelled'],
        'approved' => ['in_progress', 'waiting_parts', 'cancelled'],
        'waiting_parts' => ['in_progress', 'cancelled'],
        'in_progress' => ['quality_check', 'completed', 'cancelled'],
        'quality_check' => ['completed', 'rework', 'cancelled'],
        'rework' => ['quality_check', 'completed', 'cancelled'],
        'completed' => ['invoiced', 'closed', 'reversed'],
        'invoiced' => ['closed', 'reversed'],
        'closed' => ['reversed'],
        'cancelled' => ['reversed'],
        'reversed' => [],
    ];

    public function __construct(
        private readonly VehicleServiceJobCardRepositoryInterface $jobCardRepository,
        private readonly VehicleServiceJobCardLineRepositoryInterface $jobCardLineRepository,
        private readonly VehicleServiceJobStatusHistoryRepositoryInterface $statusHistoryRepository,
        private readonly VehicleServiceJobDocumentLinkRepositoryInterface $documentLinkRepository,
        private readonly VehicleServiceJobPaymentLinkRepositoryInterface $paymentLinkRepository,
        private readonly VehicleServiceJobInventoryLinkRepositoryInterface $inventoryLinkRepository,
        private readonly VehicleServiceSettingRepositoryInterface $settingRepository,
        private readonly DocumentOrchestrator $documentOrchestrator,
        private readonly PaymentAllocationServiceInterface $paymentAllocationService,
        private readonly CreateStockMovementServiceInterface $createStockMovementService,
        private readonly FinancePostingServiceInterface $financePostingService,
    ) {
    }

    public function transition(int|string $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById((int) $jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $targetStatus = strtolower(trim((string) ($payload['status'] ?? '')));
            if ($targetStatus === '') {
                return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'status is required.'));
            }

            $currentStatus = strtolower((string) $jobCard->get('status', 'open'));
            if (! $this->isAllowedTransition($currentStatus, $targetStatus)) {
                return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'Transition is not allowed.'));
            }

            $actorId = isset($payload['actor_id']) ? (int) $payload['actor_id'] : null;
            $tenantId = (int) $jobCard->get('tenant_id', 0);

            $fields = [
                'status' => $targetStatus,
                'updated_by' => $actorId,
            ];
            if ($targetStatus === 'completed') {
                $fields['completed_datetime'] = now()->toDateTimeString();
            }
            if ($targetStatus === 'cancelled') {
                $fields['cancelled_at'] = now()->toDateTimeString();
            }
            if ($targetStatus === 'reversed') {
                $fields['reversed_at'] = now()->toDateTimeString();
            }

            $updated = $this->jobCardRepository->update((int) $jobCardId, $fields);

            $this->statusHistoryRepository->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $jobCard->get('organization_unit_id'),
                'entity_type' => 'job_card',
                'entity_id' => (int) $jobCardId,
                'workflow_action' => 'transition',
                'from_status' => $currentStatus,
                'to_status' => $targetStatus,
                'reason' => $payload['reason'] ?? null,
                'changed_by' => $actorId,
                'changed_at' => now()->toDateTimeString(),
                'metadata' => [
                    'idempotency_key' => $payload['idempotency_key'] ?? null,
                ],
            ]);

            return Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function createInvoice(int|string $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById((int) $jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $tenantId = (int) $jobCard->get('tenant_id', 0);
            $settings = $this->resolveSettings($tenantId, $jobCard->get('organization_unit_id'));
            $documentTypeCode = (string) ($payload['document_type_code']
                ?? $settings?->get('service_invoice_document_type_code', 'VEHICLE_SERVICE_INVOICE')
                ?? 'VEHICLE_SERVICE_INVOICE');
            $documentTypeId = isset($payload['document_type_id']) ? (int) $payload['document_type_id'] : 0;
            if ($documentTypeId < 1) {
                return Result::failure(new Error(
                    VehicleServiceErrorCode::INVALID_VALUE,
                    'document_type_id is required.',
                ));
            }

            $dto = new CreateDocumentDTO(
                tenantId: $tenantId,
                documentTypeId: $documentTypeId,
                documentDate: now()->toDateString(),
                organizationUnitId: $jobCard->get('organization_unit_id') !== null
                    ? (int) $jobCard->get('organization_unit_id')
                    : null,
                ownerId: isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
                partyId: $jobCard->get('customer_id') !== null ? (int) $jobCard->get('customer_id') : null,
                dueDate: $payload['due_date'] ?? null,
                notes: $payload['notes'] ?? null,
                data: [
                    'source_type' => 'vehicle_service_job_card',
                    'source_id' => (int) $jobCardId,
                    'currency_id' => $jobCard->get('currency_id'),
                    'exchange_rate' => (float) $jobCard->get('exchange_rate', 1),
                    'document_type_code' => $documentTypeCode,
                    'job_card_number' => $jobCard->get('job_card_number'),
                ],
                items: (array) ($payload['items'] ?? []),
            );

            $document = $this->documentOrchestrator->create($dto);
            $documentId = (int) ($document->document->id ?? 0);
            if ($documentId < 1) {
                return Result::failure(new Error(
                    VehicleServiceErrorCode::INVALID_VALUE,
                    'Document creation did not return a persisted document id.',
                ));
            }

            $this->documentLinkRepository->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $jobCard->get('organization_unit_id'),
                'job_card_id' => (int) $jobCardId,
                'document_id' => $documentId,
                'document_type_code' => $documentTypeCode,
                'direction' => 'outbound',
                'status' => 'active',
                'linked_by' => $payload['actor_id'] ?? null,
                'linked_at' => now()->toDateTimeString(),
            ]);

            $this->jobCardRepository->update((int) $jobCardId, [
                'invoice_status' => 'invoiced',
                'status' => 'invoiced',
            ]);

            return Result::success([
                'job_card_id' => (int) $jobCardId,
                'document' => $document,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function allocatePayment(int|string $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById((int) $jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $documentId = isset($payload['document_id']) ? (int) $payload['document_id'] : null;
            if ($documentId === null) {
                return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, 'document_id is required.'));
            }

            $allocation = $this->paymentAllocationService->createAllocation([
                'tenant_id' => (int) $jobCard->get('tenant_id', 0),
                'organization_unit_id' => $jobCard->get('organization_unit_id'),
                'payment_id' => (int) ($payload['payment_id'] ?? 0),
                'document_type' => (string) ($payload['document_type'] ?? 'document'),
                'document_id' => $documentId,
                'allocated_amount' => (float) ($payload['amount'] ?? 0),
                'metadata' => [
                    'source_type' => 'vehicle_service_job_card',
                    'source_id' => (int) $jobCardId,
                ],
            ]);
            if ($allocation->isFailure()) {
                return Result::failure($allocation->errorOrFail());
            }
            $allocationRecord = $allocation->valueOrFail();

            $this->paymentLinkRepository->create([
                'tenant_id' => (int) $jobCard->get('tenant_id', 0),
                'organization_unit_id' => $jobCard->get('organization_unit_id'),
                'job_card_id' => (int) $jobCardId,
                'payment_id' => (int) ($payload['payment_id'] ?? 0),
                'payment_allocation_id' => is_object($allocationRecord) && method_exists($allocationRecord, 'id')
                    ? $allocationRecord->id()
                    : null,
                'allocated_amount' => (float) ($payload['amount'] ?? 0),
                'advance_amount' => (float) ($payload['advance_amount'] ?? 0),
                'refund_amount' => (float) ($payload['refund_amount'] ?? 0),
                'write_off_amount' => (float) ($payload['write_off_amount'] ?? 0),
                'status' => 'active',
                'linked_by' => $payload['actor_id'] ?? null,
                'linked_at' => now()->toDateTimeString(),
            ]);

            return Result::success($allocationRecord);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function postInventory(int|string $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById((int) $jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $tenantId = (int) $jobCard->get('tenant_id', 0);
            $lines = $this->jobCardLineRepository->list([
                'tenant_id' => $tenantId,
                'job_card_id' => (int) $jobCardId,
                'requires_stock_movement' => true,
            ]);

            $postedLinks = [];
            foreach ($lines as $line) {
                $movementPayload = [
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $jobCard->get('organization_unit_id'),
                    'movement_type' => $payload['movement_type'] ?? 'issue',
                    'item_id' => $line->get('item_id'),
                    'warehouse_id' => $line->get('warehouse_id') ?? $jobCard->get('warehouse_id'),
                    'warehouse_location_id' => $line->get('location_id'),
                    'quantity' => (float) $line->get('quantity', 0),
                    'uom_id' => $line->get('uom_id'),
                    'reference_type' => 'vehicle_service_job_card',
                    'reference_id' => (int) $jobCardId,
                    'notes' => $payload['notes'] ?? null,
                ];

                $movement = $this->createStockMovementService->execute($movementPayload);
                if ($movement->isFailure()) {
                    return Result::failure($movement->errorOrFail());
                }

                $movementValue = $movement->valueOrFail();
                $movementId = is_array($movementValue)
                    ? ($movementValue['id'] ?? null)
                    : (is_object($movementValue) && method_exists($movementValue, 'id')
                        ? $movementValue->id()
                        : null);

                $postedLinks[] = $this->inventoryLinkRepository->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $jobCard->get('organization_unit_id'),
                    'job_card_id' => (int) $jobCardId,
                    'job_card_line_id' => $line->id(),
                    'stock_movement_id' => $movementId,
                    'movement_type' => (string) ($payload['movement_type'] ?? 'consume'),
                    'quantity' => (float) $line->get('quantity', 0),
                    'quantity_base' => (float) $line->get('quantity_base', $line->get('quantity', 0)),
                    'unit_cost' => (float) $line->get('unit_cost', 0),
                    'total_cost' => round(
                        (float) $line->get('quantity', 0) * (float) $line->get('unit_cost', 0),
                        4,
                    ),
                    'status' => 'posted',
                    'posted_by' => $payload['actor_id'] ?? null,
                    'posted_at' => now()->toDateTimeString(),
                ]);
            }

            $this->jobCardRepository->update((int) $jobCardId, ['inventory_status' => 'consumed']);

            return Result::success([
                'job_card_id' => (int) $jobCardId,
                'posted_links' => $postedLinks,
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function postFinance(int|string $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById((int) $jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $posting = $this->financePostingService->postFromSource([
                'tenant_id' => (int) $jobCard->get('tenant_id', 0),
                'organization_unit_id' => $jobCard->get('organization_unit_id'),
                'entry_date' => now()->toDateString(),
                'posting_date' => now()->toDateString(),
                'reference_type' => 'vehicle_service_job_card',
                'reference_id' => (int) $jobCardId,
                'currency_id' => $jobCard->get('currency_id'),
                'exchange_rate' => (float) $jobCard->get('exchange_rate', 1),
                'amount' => (float) $jobCard->get('grand_total', 0),
                'posted_by' => $payload['actor_id'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'metadata' => [
                    'job_card_number' => $jobCard->get('job_card_number'),
                ],
            ], (array) ($payload['lines'] ?? []));
            if ($posting->isFailure()) {
                return Result::failure($posting->errorOrFail());
            }

            $this->jobCardRepository->update((int) $jobCardId, ['finance_status' => 'posted']);

            return Result::success($posting);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function reverseFinance(int|string $jobCardId, array $payload): Result
    {
        try {
            $jobCard = $this->jobCardRepository->findById((int) $jobCardId);
            if (! $jobCard instanceof DataRecord) {
                return Result::failure(new Error(VehicleServiceErrorCode::NOT_FOUND, 'Job card not found.'));
            }

            $journalEntryId = isset($payload['journal_entry_id']) ? (int) $payload['journal_entry_id'] : 0;
            if ($journalEntryId < 1) {
                return Result::failure(new Error(
                    VehicleServiceErrorCode::INVALID_VALUE,
                    'journal_entry_id is required for finance reversal.',
                ));
            }

            $reversal = $this->financePostingService->reverseByEntryId($journalEntryId, [
                'reason' => $payload['reason'] ?? 'Vehicle service reversal',
                'metadata' => [
                    'job_card_number' => $jobCard->get('job_card_number'),
                ],
            ]);
            if ($reversal->isFailure()) {
                return Result::failure($reversal->errorOrFail());
            }

            $this->jobCardRepository->update((int) $jobCardId, [
                'finance_status' => 'reversed',
                'status' => 'reversed',
                'reversed_at' => now()->toDateTimeString(),
            ]);

            return Result::success($reversal);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleServiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function isAllowedTransition(string $fromStatus, string $toStatus): bool
    {
        if (! array_key_exists($fromStatus, self::STATUS_TRANSITION_MATRIX)) {
            return false;
        }

        return in_array($toStatus, self::STATUS_TRANSITION_MATRIX[$fromStatus], true);
    }

    private function resolveSettings(int $tenantId, mixed $organizationUnitId): ?DataRecord
    {
        $records = $organizationUnitId !== null
            ? $this->settingRepository->list([
                'tenant_id' => $tenantId,
                'organization_unit_id' => (int) $organizationUnitId,
                'is_active' => true,
            ])
            : [];

        if ($records === []) {
            $records = $this->settingRepository->list([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'is_active' => true,
            ]);
        }

        return $records[0] ?? null;
    }
}
