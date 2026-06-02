<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Document\Application\DTOs\CreateDocumentDTO;
use Modules\Document\Application\Services\DocumentOrchestrator;
use Modules\Finance\Application\Contracts\Services\FinancePostingServiceInterface;
use Modules\Payment\Application\Contracts\Services\PaymentAllocationServiceInterface;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalWorkflowServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalAgreementLineRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalAgreementRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalApprovalHistoryRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalDocumentLinkRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalExtraChargeRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalPaymentLinkRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalProviderPayableRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalRunningChartRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalSettingRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalStatusHistoryRepositoryInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Throwable;

final class VehicleRentalWorkflowService implements VehicleRentalWorkflowServiceInterface
{
    /** @var array<string, list<string>> */
    private const AGREEMENT_TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['started', 'cancelled'],
        'started' => ['completed', 'cancelled'],
        'completed' => ['closed', 'reversed'],
        'closed' => ['reversed'],
        'cancelled' => ['reversed'],
        'reversed' => [],
    ];

    /** @var array<string, list<string>> */
    private const RUNNING_CHART_TRANSITIONS = [
        'draft' => ['submitted', 'cancelled'],
        'submitted' => ['approved', 'cancelled'],
        'approved' => ['invoiced', 'cancelled'],
        'invoiced' => ['cancelled'],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly VehicleRentalAgreementRepositoryInterface $agreementRepository,
        private readonly VehicleRentalAgreementLineRepositoryInterface $agreementLineRepository,
        private readonly VehicleRentalRunningChartRepositoryInterface $runningChartRepository,
        private readonly VehicleRentalExtraChargeRepositoryInterface $extraChargeRepository,
        private readonly VehicleRentalProviderPayableRepositoryInterface $providerPayableRepository,
        private readonly VehicleRentalDocumentLinkRepositoryInterface $documentLinkRepository,
        private readonly VehicleRentalPaymentLinkRepositoryInterface $paymentLinkRepository,
        private readonly VehicleRentalStatusHistoryRepositoryInterface $statusHistoryRepository,
        private readonly VehicleRentalApprovalHistoryRepositoryInterface $approvalHistoryRepository,
        private readonly VehicleRentalSettingRepositoryInterface $settingRepository,
        private readonly DocumentOrchestrator $documentOrchestrator,
        private readonly PaymentAllocationServiceInterface $paymentAllocationService,
        private readonly FinancePostingServiceInterface $financePostingService,
    ) {
    }

    public function transitionAgreement(int|string $agreementId, array $payload): Result
    {
        try {
            $agreement = $this->agreementRepository->findById((int) $agreementId);
            if (! $agreement instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
            }
            if ((string) $agreement->get('agreement_role', 'lessee') !== 'lessee') {
                return Result::failure(new Error(
                    VehicleRentalErrorCode::INVALID_VALUE,
                    'Lessee invoice generation requires a lessee agreement.',
                ));
            }

            $targetStatus = strtolower(trim((string) ($payload['status'] ?? '')));
            if ($targetStatus === '') {
                return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, 'status is required.'));
            }

            $currentStatus = strtolower((string) $agreement->get('status', 'draft'));
            if (! $this->isAllowedTransition(self::AGREEMENT_TRANSITIONS, $currentStatus, $targetStatus)) {
                return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, 'Transition is not allowed.'));
            }

            $updates = [
                'status' => $targetStatus,
                'updated_by' => $payload['actor_id'] ?? null,
            ];

            match ($targetStatus) {
                'confirmed' => $updates['confirmed_at'] = now()->toDateTimeString(),
                'started' => $updates['started_at'] = now()->toDateTimeString(),
                'completed' => $updates['completed_at'] = now()->toDateTimeString(),
                'closed' => $updates['closed_at'] = now()->toDateTimeString(),
                'cancelled' => $updates['cancelled_at'] = now()->toDateTimeString(),
                'reversed' => $updates['reversed_at'] = now()->toDateTimeString(),
                default => null,
            };
            match ($targetStatus) {
                'confirmed' => $updates['confirmed_by'] = $payload['actor_id'] ?? null,
                'started' => $updates['started_by'] = $payload['actor_id'] ?? null,
                'completed' => $updates['completed_by'] = $payload['actor_id'] ?? null,
                'closed' => $updates['closed_by'] = $payload['actor_id'] ?? null,
                'cancelled' => $updates['cancelled_by'] = $payload['actor_id'] ?? null,
                'reversed' => $updates['reversed_by'] = $payload['actor_id'] ?? null,
                default => null,
            };

            $updated = $this->agreementRepository->update((int) $agreementId, $updates);
            $this->recordStatusHistory(
                'agreement',
                (int) $agreementId,
                (int) $agreement->get('tenant_id', 0),
                $agreement->get('organization_unit_id'),
                $currentStatus,
                $targetStatus,
                $payload,
            );

            if (in_array($targetStatus, ['confirmed', 'closed'], true)) {
                $this->recordApprovalHistory(
                    'agreement',
                    (int) $agreementId,
                    (int) $agreement->get('tenant_id', 0),
                    $agreement->get('organization_unit_id'),
                    $targetStatus,
                    $payload,
                );
            }

            return Result::success($this->normalizeRecord($updated));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function transitionRunningChart(int|string $runningChartId, array $payload): Result
    {
        try {
            $runningChart = $this->runningChartRepository->findById((int) $runningChartId);
            if (! $runningChart instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Running chart not found.'));
            }

            $targetStatus = strtolower(trim((string) ($payload['status'] ?? '')));
            if ($targetStatus === '') {
                return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, 'status is required.'));
            }

            $currentStatus = strtolower((string) $runningChart->get('status', 'draft'));
            if (! $this->isAllowedTransition(self::RUNNING_CHART_TRANSITIONS, $currentStatus, $targetStatus)) {
                return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, 'Transition is not allowed.'));
            }

            $updates = [
                'status' => $targetStatus,
                'updated_by' => $payload['actor_id'] ?? null,
            ];
            match ($targetStatus) {
                'submitted' => $updates['submitted_at'] = now()->toDateTimeString(),
                'approved' => $updates['approved_at'] = now()->toDateTimeString(),
                'invoiced' => $updates['invoiced_at'] = now()->toDateTimeString(),
                'cancelled' => $updates['cancelled_at'] = now()->toDateTimeString(),
                default => null,
            };
            match ($targetStatus) {
                'submitted' => $updates['submitted_by'] = $payload['actor_id'] ?? null,
                'approved' => $updates['approved_by'] = $payload['actor_id'] ?? null,
                'invoiced' => $updates['invoiced_by'] = $payload['actor_id'] ?? null,
                'cancelled' => $updates['cancelled_by'] = $payload['actor_id'] ?? null,
                default => null,
            };

            $updated = $this->runningChartRepository->update((int) $runningChartId, $updates);
            $this->recordStatusHistory(
                'running_chart',
                (int) $runningChartId,
                (int) $runningChart->get('tenant_id', 0),
                $runningChart->get('organization_unit_id'),
                $currentStatus,
                $targetStatus,
                $payload,
            );

            if (in_array($targetStatus, ['approved'], true)) {
                $this->recordApprovalHistory(
                    'running_chart',
                    (int) $runningChartId,
                    (int) $runningChart->get('tenant_id', 0),
                    $runningChart->get('organization_unit_id'),
                    'approved',
                    $payload,
                );
            }

            return Result::success($this->normalizeRecord($updated));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function createInvoice(int|string $agreementId, array $payload): Result
    {
        try {
            $agreement = $this->agreementRepository->findById((int) $agreementId);
            if (! $agreement instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
            }
            if ((string) $agreement->get('agreement_role', 'lessee') !== 'lessee') {
                return Result::failure(new Error(
                    VehicleRentalErrorCode::INVALID_VALUE,
                    'Lessee payment allocation requires a lessee agreement.',
                ));
            }

            $documentTypeId = isset($payload['document_type_id']) ? (int) $payload['document_type_id'] : 0;
            if ($documentTypeId < 1) {
                $settings = $this->resolveSettings(
                    (int) $agreement->get('tenant_id', 0),
                    $agreement->get('organization_unit_id') !== null
                        ? (int) $agreement->get('organization_unit_id')
                        : null,
                );
                $documentTypeId = (int) ($settings?->get('rental_invoice_document_definition_id', 0) ?? 0);
            }
            if ($documentTypeId < 1) {
                return Result::failure(new Error(
                    VehicleRentalErrorCode::INVALID_VALUE,
                    'document_type_id is required.',
                ));
            }

            $items = is_array($payload['items'] ?? null)
                ? $payload['items']
                : $this->buildInvoiceItems((int) $agreementId);
            $dto = new CreateDocumentDTO(
                tenantId: (int) $agreement->get('tenant_id', 0),
                documentTypeId: $documentTypeId,
                documentDate: $payload['document_date'] ?? now()->toDateString(),
                organizationUnitId: $agreement->get('organization_unit_id') !== null
                    ? (int) $agreement->get('organization_unit_id')
                    : null,
                ownerId: isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
                partyId: $agreement->get('customer_id') !== null ? (int) $agreement->get('customer_id') : null,
                dueDate: $payload['due_date'] ?? null,
                notes: $payload['notes'] ?? null,
                data: [
                    'source_type' => 'vehicle_rental_agreement',
                    'source_id' => (int) $agreementId,
                    'agreement_number' => $agreement->get('agreement_number'),
                    'document_role' => 'rental_invoice',
                    'currency_id' => $agreement->get('currency_id'),
                    'exchange_rate' => (float) $agreement->get('exchange_rate', 1),
                ],
                items: $items,
            );

            $document = $this->documentOrchestrator->create($dto);
            $documentId = (int) ($document->document->id ?? 0);
            if ($documentId < 1) {
                return Result::failure(new Error(
                    VehicleRentalErrorCode::INVALID_VALUE,
                    'Document creation did not return a persisted document id.',
                ));
            }

            $this->documentLinkRepository->create([
                'tenant_id' => (int) $agreement->get('tenant_id', 0),
                'organization_unit_id' => $agreement->get('organization_unit_id'),
                'agreement_id' => (int) $agreementId,
                'document_id' => $documentId,
                'document_definition_id' => $documentTypeId,
                'document_role' => 'rental_invoice',
                'entity_type' => 'agreement',
                'entity_id' => (int) $agreementId,
                'status' => 'active',
                'linked_by' => $payload['actor_id'] ?? null,
                'linked_at' => now()->toDateTimeString(),
            ]);

            $updated = $this->agreementRepository->update((int) $agreementId, [
                'invoice_status' => 'invoiced',
                'status' => in_array((string) $agreement->get('status'), ['completed', 'closed'], true)
                    ? $agreement->get('status')
                    : 'completed',
                'invoiced_total' => round(
                    (float) ($payload['invoice_total'] ?? $agreement->get('estimated_grand_total', 0)),
                    4,
                ),
                'outstanding_balance' => round(
                    (float) ($payload['invoice_total'] ?? $agreement->get('estimated_grand_total', 0)),
                    4,
                ),
            ]);

            $this->recordStatusHistory(
                'agreement',
                (int) $agreementId,
                (int) $agreement->get('tenant_id', 0),
                $agreement->get('organization_unit_id'),
                (string) $agreement->get('status', 'draft'),
                (string) $updated->get('status', 'completed'),
                ['reason' => 'invoice_created', 'actor_id' => $payload['actor_id'] ?? null],
            );

            return Result::success([
                'agreement_id' => (int) $agreementId,
                'document' => $document,
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function allocateCustomerPayment(int|string $agreementId, array $payload): Result
    {
        try {
            $agreement = $this->agreementRepository->findById((int) $agreementId);
            if (! $agreement instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
            }

            $documentLink = $this->resolveAgreementDocumentLink((int) $agreementId);
            if (! $documentLink instanceof DataRecord) {
                return Result::failure(new Error(
                    VehicleRentalErrorCode::INVALID_VALUE,
                    'Agreement invoice document link is required before payment allocation.',
                ));
            }

            $allocation = $this->paymentAllocationService->createAllocation([
                'tenant_id' => (int) $agreement->get('tenant_id', 0),
                'organization_unit_id' => $agreement->get('organization_unit_id'),
                'payment_id' => (int) ($payload['payment_id'] ?? 0),
                'document_type' => 'document',
                'document_id' => (int) $documentLink->get('document_id', 0),
                'allocated_amount' => (float) ($payload['amount'] ?? 0),
                'metadata' => [
                    'source_type' => 'vehicle_rental_agreement',
                    'source_id' => (int) $agreementId,
                ],
            ]);
            if ($allocation->isFailure()) {
                return $allocation;
            }

            $allocationRecord = $allocation->valueOrFail();
            $newPaidTotal = round((float) $agreement->get('paid_total', 0) + (float) ($payload['amount'] ?? 0), 4);
            $outstandingBalance = round(max(0.0, (float) $agreement->get('invoiced_total', 0) - $newPaidTotal), 4);

            $this->paymentLinkRepository->create([
                'tenant_id' => (int) $agreement->get('tenant_id', 0),
                'organization_unit_id' => $agreement->get('organization_unit_id'),
                'agreement_id' => (int) $agreementId,
                'provider_payable_id' => null,
                'document_link_id' => (int) $documentLink->id(),
                'payment_id' => (int) ($payload['payment_id'] ?? 0),
                'payment_allocation_id' => is_object($allocationRecord) && method_exists($allocationRecord, 'id')
                    ? $allocationRecord->id()
                    : null,
                'payment_direction' => 'incoming',
                'payment_role' => (string) ($payload['payment_role'] ?? 'settlement'),
                'status' => 'active',
                'amount' => (float) ($payload['amount'] ?? 0),
                'refund_amount' => (float) ($payload['refund_amount'] ?? 0),
                'write_off_amount' => (float) ($payload['write_off_amount'] ?? 0),
                'linked_by' => $payload['actor_id'] ?? null,
                'linked_at' => now()->toDateTimeString(),
            ]);

            $updated = $this->agreementRepository->update((int) $agreementId, [
                'paid_total' => $newPaidTotal,
                'payment_status' => $outstandingBalance <= 0.0001 ? 'paid' : 'partially_paid',
                'outstanding_balance' => $outstandingBalance,
            ]);

            return Result::success($this->normalizeRecord($updated));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function createProviderPayable(int|string $agreementId, array $payload): Result
    {
        try {
            $agreement = $this->agreementRepository->findById((int) $agreementId);
            if (! $agreement instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Agreement not found.'));
            }
            if ((string) $agreement->get('agreement_role', 'lessee') !== 'lessor') {
                return Result::failure(new Error(
                    VehicleRentalErrorCode::INVALID_VALUE,
                    'Provider payable generation requires a lessor agreement.',
                ));
            }

            $grandTotal = (float) (
                $payload['grand_total'] ?? $this->resolveDefaultProviderPayableAmount((int) $agreementId)
            );
            $providerId = (int) ($payload['provider_id'] ?? $agreement->get('provider_id', 0));
            $payable = $this->providerPayableRepository->create([
                'row_version' => 1,
                'tenant_id' => (int) $agreement->get('tenant_id', 0),
                'organization_unit_id' => $agreement->get('organization_unit_id'),
                'agreement_id' => (int) $agreementId,
                'provider_id' => $providerId > 0 ? $providerId : null,
                'provider_party_type' => $payload['provider_party_type'] ?? $agreement->get('lessor_party_type'),
                'provider_party_id' => $payload['provider_party_id'] ?? $agreement->get('lessor_party_id'),
                'provider_party_name' => $payload['provider_party_name'] ?? $agreement->get('lessor_party_name'),
                'rental_vehicle_id' => $payload['rental_vehicle_id'] ?? $agreement->get('rental_vehicle_id'),
                'replacement_id' => $payload['replacement_id'] ?? null,
                'currency_id' => $payload['currency_id'] ?? $agreement->get('currency_id'),
                'payable_number' => (string) (
                    $payload['payable_number']
                    ?? $this->generatePayableNumber((int) $agreement->get('tenant_id', 0))
                ),
                'source_entity_type' => (string) ($payload['source_entity_type'] ?? 'agreement'),
                'source_entity_id' => (int) ($payload['source_entity_id'] ?? $agreementId),
                'status' => (string) ($payload['status'] ?? 'approved'),
                'payment_status' => 'unpaid',
                'finance_status' => 'draft',
                'payable_date' => $payload['payable_date'] ?? now()->toDateString(),
                'due_date' => $payload['due_date'] ?? null,
                'exchange_rate' => (float) ($payload['exchange_rate'] ?? $agreement->get('exchange_rate', 1)),
                'subtotal' => (float) ($payload['subtotal'] ?? $grandTotal),
                'discount_total' => (float) ($payload['discount_total'] ?? 0),
                'tax_total' => (float) ($payload['tax_total'] ?? 0),
                'grand_total' => $grandTotal,
                'paid_total' => 0,
                'balance' => $grandTotal,
                'notes' => $payload['notes'] ?? null,
                'approved_by' => $payload['actor_id'] ?? null,
                'approved_at' => now()->toDateTimeString(),
            ]);

            $updatedAgreement = $this->agreementRepository->update((int) $agreementId, [
                'provider_payable_total' => round(
                    (float) $agreement->get('provider_payable_total', 0) + $grandTotal,
                    4,
                ),
            ]);

            $this->recordApprovalHistory(
                'provider_payable',
                (int) $payable->id(),
                (int) $agreement->get('tenant_id', 0),
                $agreement->get('organization_unit_id'),
                'approved',
                ['remarks' => 'provider_payable_created', 'actor_id' => $payload['actor_id'] ?? null],
            );

            return Result::success([
                'provider_payable' => $this->normalizeRecord($payable),
                'agreement' => $this->normalizeRecord($updatedAgreement),
            ]);
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function allocateProviderPayment(int|string $providerPayableId, array $payload): Result
    {
        try {
            $payable = $this->providerPayableRepository->findById((int) $providerPayableId);
            if (! $payable instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Provider payable not found.'));
            }

            $allocation = $this->paymentAllocationService->createAllocation([
                'tenant_id' => (int) $payable->get('tenant_id', 0),
                'organization_unit_id' => $payable->get('organization_unit_id'),
                'payment_id' => (int) ($payload['payment_id'] ?? 0),
                'document_type' => 'vehicle_rental_provider_payable',
                'document_id' => (int) $providerPayableId,
                'allocated_amount' => (float) ($payload['amount'] ?? 0),
                'metadata' => [
                    'source_type' => 'vehicle_rental_provider_payable',
                    'source_id' => (int) $providerPayableId,
                ],
            ]);
            if ($allocation->isFailure()) {
                return $allocation;
            }

            $allocationRecord = $allocation->valueOrFail();
            $newPaidTotal = round((float) $payable->get('paid_total', 0) + (float) ($payload['amount'] ?? 0), 4);
            $balance = round(max(0.0, (float) $payable->get('grand_total', 0) - $newPaidTotal), 4);

            $this->paymentLinkRepository->create([
                'tenant_id' => (int) $payable->get('tenant_id', 0),
                'organization_unit_id' => $payable->get('organization_unit_id'),
                'agreement_id' => $payable->get('agreement_id'),
                'provider_payable_id' => (int) $providerPayableId,
                'document_link_id' => null,
                'payment_id' => (int) ($payload['payment_id'] ?? 0),
                'payment_allocation_id' => is_object($allocationRecord) && method_exists($allocationRecord, 'id')
                    ? $allocationRecord->id()
                    : null,
                'payment_direction' => 'outgoing',
                'payment_role' => 'provider_payment',
                'status' => 'active',
                'amount' => (float) ($payload['amount'] ?? 0),
                'refund_amount' => 0,
                'write_off_amount' => 0,
                'linked_by' => $payload['actor_id'] ?? null,
                'linked_at' => now()->toDateTimeString(),
            ]);

            $updated = $this->providerPayableRepository->update((int) $providerPayableId, [
                'paid_total' => $newPaidTotal,
                'balance' => $balance,
                'payment_status' => $balance <= 0.0001 ? 'paid' : 'partially_paid',
            ]);

            if ($updated->get('agreement_id') !== null) {
                $agreement = $this->agreementRepository->findById((int) $updated->get('agreement_id'));
                if ($agreement instanceof DataRecord) {
                    $this->agreementRepository->update((int) $agreement->id(), [
                        'provider_paid_total' => round(
                            (float) $agreement->get('provider_paid_total', 0) + (float) ($payload['amount'] ?? 0),
                            4,
                        ),
                    ]);
                }
            }

            return Result::success($this->normalizeRecord($updated));
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function postFinance(string $entityType, int|string $entityId, array $payload): Result
    {
        try {
            $record = $this->findEntity($entityType, $entityId);
            if (! $record instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Entity not found.'));
            }

            $entryPayload = [
                'tenant_id' => (int) $record->get('tenant_id', 0),
                'organization_unit_id' => $record->get('organization_unit_id'),
                'source_type' => 'vehicle_rental_' . $entityType,
                'source_id' => (int) $record->id(),
                'posting_date' => $payload['posting_date'] ?? now()->toDateString(),
                'reference' => $payload['reference'] ?? null,
                'memo' => $payload['memo'] ?? null,
                'currency_id' => $payload['currency_id'] ?? $record->get('currency_id'),
                'exchange_rate' => (float) ($payload['exchange_rate'] ?? $record->get('exchange_rate', 1)),
            ];
            $linesPayload = is_array($payload['lines'] ?? null)
                ? $payload['lines']
                : $this->buildFinanceLines($entityType, $record);

            $posting = $this->financePostingService->postFromSource($entryPayload, $linesPayload);
            if ($posting->isFailure()) {
                return $posting;
            }

            $this->updateFinanceStatus($entityType, (int) $entityId, 'posted', $payload['actor_id'] ?? null);
            $this->recordStatusHistory(
                $entityType,
                (int) $entityId,
                (int) $record->get('tenant_id', 0),
                $record->get('organization_unit_id'),
                (string) $record->get('status', ''),
                (string) $record->get('status', ''),
                ['reason' => 'finance_post', 'actor_id' => $payload['actor_id'] ?? null],
            );

            return Result::success($posting->valueOrFail());
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    public function reverseFinance(string $entityType, int|string $entityId, array $payload): Result
    {
        try {
            $record = $this->findEntity($entityType, $entityId);
            if (! $record instanceof DataRecord) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'Entity not found.'));
            }

            $journalEntryId = $payload['journal_entry_id'] ?? null;
            if (! is_int($journalEntryId) && ! is_string($journalEntryId)) {
                return Result::failure(new Error(
                    VehicleRentalErrorCode::INVALID_VALUE,
                    'journal_entry_id is required.',
                ));
            }

            $reversal = $this->financePostingService->reverseByEntryId($journalEntryId, [
                'tenant_id' => (int) $record->get('tenant_id', 0),
                'organization_unit_id' => $record->get('organization_unit_id'),
                'reason' => $payload['reason'] ?? null,
                'reversed_by' => $payload['actor_id'] ?? null,
            ]);
            if ($reversal->isFailure()) {
                return $reversal;
            }

            $this->updateFinanceStatus($entityType, (int) $entityId, 'reversed', $payload['actor_id'] ?? null);
            $this->recordStatusHistory(
                $entityType,
                (int) $entityId,
                (int) $record->get('tenant_id', 0),
                $record->get('organization_unit_id'),
                (string) $record->get('status', ''),
                (string) $record->get('status', ''),
                ['reason' => 'finance_reverse', 'actor_id' => $payload['actor_id'] ?? null],
            );

            return Result::success($reversal->valueOrFail());
        } catch (Throwable $exception) {
            return $this->failure($exception);
        }
    }

    private function buildInvoiceItems(int $agreementId): array
    {
        $items = [];
        $lines = $this->agreementLineRepository->list(['agreement_id' => $agreementId]);
        foreach ($lines as $line) {
            if (! $line instanceof DataRecord || ! (bool) $line->get('is_billable', true)) {
                continue;
            }

            $items[] = [
                'description' => $line->get('description'),
                'quantity' => (float) $line->get('quantity', 0),
                'unit_price' => (float) $line->get('unit_rate', 0),
                'discount_amount' => (float) $line->get('discount_amount', 0),
                'tax_amount' => (float) $line->get('tax_amount', 0),
                'line_total' => (float) $line->get('line_total', 0),
                'uom_id' => $line->get('uom_id'),
                'item_id' => $line->get('item_id'),
            ];
        }

        $extraCharges = $this->extraChargeRepository->list(['agreement_id' => $agreementId]);
        foreach ($extraCharges as $extraCharge) {
            if (
                ! $extraCharge instanceof DataRecord
                || (string) $extraCharge->get('charge_scope', 'customer') === 'provider'
            ) {
                continue;
            }

            $items[] = [
                'description' => $extraCharge->get('description'),
                'quantity' => (float) $extraCharge->get('quantity', 0),
                'unit_price' => (float) $extraCharge->get('unit_amount', 0),
                'discount_amount' => (float) $extraCharge->get('discount_amount', 0),
                'tax_amount' => (float) $extraCharge->get('tax_amount', 0),
                'line_total' => (float) $extraCharge->get('total_amount', 0),
                'uom_id' => $extraCharge->get('uom_id'),
                'item_id' => $extraCharge->get('item_id'),
            ];
        }

        return $items;
    }

    private function buildFinanceLines(string $entityType, DataRecord $record): array
    {
        if ($entityType === 'provider_payable') {
            return [[
                'description' => 'Vehicle rental provider payable',
                'debit_amount' => (float) $record->get('grand_total', 0),
                'credit_amount' => (float) $record->get('grand_total', 0),
                'account_id' => null,
                'reference' => $record->get('payable_number'),
            ]];
        }

        return [[
            'description' => 'Vehicle rental agreement invoice',
            'debit_amount' => (float) $record->get('invoiced_total', 0),
            'credit_amount' => (float) $record->get('invoiced_total', 0),
            'account_id' => null,
            'reference' => $record->get('agreement_number'),
        ]];
    }

    private function resolveDefaultProviderPayableAmount(int $agreementId): float
    {
        $lines = $this->agreementLineRepository->list(['agreement_id' => $agreementId]);
        $amount = 0.0;
        foreach ($lines as $line) {
            if (! $line instanceof DataRecord || ! (bool) $line->get('is_payable', false)) {
                continue;
            }
            $amount += (float) $line->get('line_total', 0);
        }

        $extraCharges = $this->extraChargeRepository->list(['agreement_id' => $agreementId]);
        foreach ($extraCharges as $extraCharge) {
            if (
                ! $extraCharge instanceof DataRecord
                || (string) $extraCharge->get('charge_scope', 'customer') !== 'provider'
            ) {
                continue;
            }
            $amount += (float) $extraCharge->get('total_amount', 0);
        }

        return round($amount, 4);
    }

    private function generatePayableNumber(int $tenantId): string
    {
        return 'VRP-' . $tenantId . '-' . now()->format('YmdHis');
    }

    private function resolveAgreementDocumentLink(int $agreementId): ?DataRecord
    {
        $documentLinks = $this->documentLinkRepository->list([
            'agreement_id' => $agreementId,
            'entity_type' => 'agreement',
        ]);
        foreach ($documentLinks as $documentLink) {
            if (
                $documentLink instanceof DataRecord
                && (string) $documentLink->get('document_role', '') === 'rental_invoice'
            ) {
                return $documentLink;
            }
        }

        return null;
    }

    private function findEntity(string $entityType, int|string $entityId): ?DataRecord
    {
        return match ($entityType) {
            'agreement' => $this->agreementRepository->findById((int) $entityId),
            'provider_payable' => $this->providerPayableRepository->findById((int) $entityId),
            'running_chart' => $this->runningChartRepository->findById((int) $entityId),
            default => null,
        };
    }

    private function updateFinanceStatus(string $entityType, int $entityId, string $financeStatus, mixed $actorId): void
    {
        match ($entityType) {
            'agreement' => $this->agreementRepository->update($entityId, [
                'finance_status' => $financeStatus,
                'updated_by' => $actorId,
            ]),
            'provider_payable' => $this->providerPayableRepository->update($entityId, [
                'finance_status' => $financeStatus,
                'posted_by' => $financeStatus === 'posted' ? $actorId : null,
                'posted_at' => $financeStatus === 'posted' ? now()->toDateTimeString() : null,
                'reversed_by' => $financeStatus === 'reversed' ? $actorId : null,
                'reversed_at' => $financeStatus === 'reversed' ? now()->toDateTimeString() : null,
            ]),
            default => null,
        };
    }

    private function recordStatusHistory(
        string $entityType,
        int $entityId,
        int $tenantId,
        mixed $organizationUnitId,
        ?string $fromStatus,
        string $toStatus,
        array $payload,
    ): void {
        $this->statusHistoryRepository->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action_name' => $payload['action_name'] ?? 'transition',
            'reason' => $payload['reason'] ?? null,
            'changed_by' => $payload['actor_id'] ?? null,
            'changed_at' => now()->toDateTimeString(),
        ]);
    }

    private function recordApprovalHistory(
        string $entityType,
        int $entityId,
        int $tenantId,
        mixed $organizationUnitId,
        string $approvalStatus,
        array $payload,
    ): void {
        $this->approvalHistoryRepository->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'approval_step' => $payload['approval_step'] ?? 'default',
            'approval_status' => $approvalStatus,
            'remarks' => $payload['remarks'] ?? null,
            'approved_by' => $payload['actor_id'] ?? null,
            'approved_at' => now()->toDateTimeString(),
        ]);
    }

    private function resolveSettings(int $tenantId, ?int $organizationUnitId): ?DataRecord
    {
        $records = $this->settingRepository->list(['tenant_id' => $tenantId]);
        $fallback = null;

        foreach ($records as $record) {
            if (! $record instanceof DataRecord) {
                continue;
            }

            if ($record->get('organization_unit_id') === null) {
                $fallback = $record;
            }

            if ($organizationUnitId !== null && (int) $record->get('organization_unit_id', 0) === $organizationUnitId) {
                return $record;
            }
        }

        return $fallback;
    }

    /**
     * @param array<string, list<string>> $matrix
     */
    private function isAllowedTransition(array $matrix, string $currentStatus, string $targetStatus): bool
    {
        return in_array($targetStatus, $matrix[$currentStatus] ?? [], true);
    }

    private function normalizeRecord(DataRecord $record): array
    {
        return array_merge(['id' => $record->id()], $record->toArray());
    }

    private function failure(Throwable $exception): Result
    {
        return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, $exception->getMessage()));
    }
}
