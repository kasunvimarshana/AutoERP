<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentAllocationServiceInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class AdvancePaymentAllocationService implements AdvancePaymentAllocationServiceInterface
{
    public function __construct(
        private readonly AdvancePaymentAllocationRepositoryInterface $advancePaymentAllocationRepository,
        private readonly AdvancePaymentRepositoryInterface $advancePaymentRepository,
    ) {
    }

    public function createAllocation(array $payload): Result
    {
        try {
            $advancePaymentId = isset($payload['advance_payment_id']) ? (int) $payload['advance_payment_id'] : 0;
            $documentType = trim((string) ($payload['document_type'] ?? ''));
            $documentId = isset($payload['document_id']) ? (int) $payload['document_id'] : 0;
            $allocatedAmount = round((float) ($payload['allocated_amount'] ?? 0), 4);

            if ($advancePaymentId < 1 || $documentType === '' || $documentId < 1 || $allocatedAmount <= 0) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'advance_payment_id, document_type, document_id and allocated_amount are required.',
                ));
            }

            $advance = $this->advancePaymentRepository->findById($advancePaymentId);
            if (! $advance instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Advance payment not found.'));
            }

            $remainingAmount = round((float) $advance->get('remaining_amount', 0), 4);
            if (
                $remainingAmount <= 0
                || in_array(
                    strtolower((string) $advance->get('status', 'open')),
                    ['fully_applied', 'cancelled', 'refunded'],
                    true,
                )
            ) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Advance payment has no allocatable balance.',
                ));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $advance->get('tenant_id', 0));
            if ($tenantId < 1 || $tenantId !== (int) $advance->get('tenant_id', 0)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Cross-tenant advance allocation is not allowed.',
                ));
            }

            if ($allocatedAmount > $remainingAmount) {
                return Result::failure(new Error(
                    PaymentErrorCode::INSUFFICIENT_UNALLOCATED_AMOUNT,
                    'Allocated amount exceeds remaining advance amount.',
                ));
            }

            return $this->advancePaymentRepository->transaction(function () use (
                $payload,
                $advancePaymentId,
                $documentType,
                $documentId,
                $allocatedAmount,
                $advance,
                $remainingAmount,
                $tenantId,
            ): Result {
                $allocation = $this->advancePaymentAllocationRepository->create([
                    'row_version' => (int) ($payload['row_version'] ?? 1),
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $payload['organization_unit_id'] ?? $advance->get('organization_unit_id'),
                    'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                    'advance_payment_id' => $advancePaymentId,
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                    'reference' => $payload['reference'] ?? null,
                    'allocated_amount' => $allocatedAmount,
                ]);

                $newRemaining = round($remainingAmount - $allocatedAmount, 4);
                $this->advancePaymentRepository->update($advancePaymentId, [
                    'remaining_amount' => $newRemaining,
                    'status' => $this->resolveAdvanceStatus($newRemaining, (float) $advance->get('amount', 0)),
                    'row_version' => ((int) $advance->get('row_version', 1)) + 1,
                ]);

                return Result::success($allocation);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateAllocation(int|string $id, array $payload): Result
    {
        try {
            $allocation = $this->advancePaymentAllocationRepository->findById($id);
            if (! $allocation instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Advance payment allocation not found.'));
            }

            if ($this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'advance_payment_id, document_type and document_id are immutable for advance allocations.',
                ));
            }

            $advancePaymentId = (int) $allocation->get('advance_payment_id', 0);
            $advance = $this->advancePaymentRepository->findById($advancePaymentId);
            if (! $advance instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Advance payment not found.'));
            }

            $newAmount = array_key_exists('allocated_amount', $payload)
                ? round((float) $payload['allocated_amount'], 4)
                : round((float) $allocation->get('allocated_amount', 0), 4);
            if ($newAmount <= 0) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'allocated_amount must be greater than zero.',
                ));
            }

            return $this->advancePaymentRepository->transaction(function () use (
                $id,
                $payload,
                $newAmount,
                $allocation,
                $advance,
                $advancePaymentId,
            ): Result {
                $totalWithoutCurrent = $this->sumAdvanceAllocations($advancePaymentId, (int) $allocation->id());
                $advanceAmount = round((float) $advance->get('amount', 0), 4);
                if ($totalWithoutCurrent + $newAmount > $advanceAmount) {
                    return Result::failure(new Error(
                        PaymentErrorCode::INSUFFICIENT_UNALLOCATED_AMOUNT,
                        'Allocated amount exceeds remaining advance amount.',
                    ));
                }

                $updated = $this->advancePaymentAllocationRepository->update($id, array_merge($payload, [
                    'allocated_amount' => $newAmount,
                ]));

                $newRemaining = round($advanceAmount - ($totalWithoutCurrent + $newAmount), 4);
                $this->advancePaymentRepository->update($advancePaymentId, [
                    'remaining_amount' => $newRemaining,
                    'status' => $this->resolveAdvanceStatus($newRemaining, $advanceAmount),
                    'row_version' => ((int) $advance->get('row_version', 1)) + 1,
                ]);

                return Result::success($updated);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function sumAdvanceAllocations(int $advancePaymentId, ?int $excludingAllocationId = null): float
    {
        $sum = 0.0;
        $allocations = $this->advancePaymentAllocationRepository->list(['advance_payment_id' => $advancePaymentId]);
        foreach ($allocations as $allocation) {
            if (! $allocation instanceof DataRecord) {
                continue;
            }

            if ($excludingAllocationId !== null && (int) $allocation->id() === $excludingAllocationId) {
                continue;
            }

            $sum += (float) $allocation->get('allocated_amount', 0);
        }

        return round($sum, 4);
    }

    private function resolveAdvanceStatus(float $remainingAmount, float $advanceAmount): string
    {
        if ($remainingAmount <= 0) {
            return 'fully_applied';
        }

        if ($remainingAmount < $advanceAmount) {
            return 'partially_applied';
        }

        return 'open';
    }

    /** @param array<string, mixed> $payload */
    private function containsStructuralMutation(array $payload): bool
    {
        foreach (['advance_payment_id', 'document_type', 'document_id'] as $field) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
