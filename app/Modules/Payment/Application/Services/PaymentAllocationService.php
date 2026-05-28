<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\PaymentAllocationServiceInterface;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Modules\Payment\Domain\Constants\PaymentStatus;
use Throwable;

final class PaymentAllocationService implements PaymentAllocationServiceInterface
{
    public function __construct(
        private readonly PaymentAllocationRepositoryInterface $paymentAllocationRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    public function createAllocation(array $payload): Result
    {
        try {
            $paymentId = isset($payload['payment_id']) ? (int) $payload['payment_id'] : 0;
            $documentType = trim((string) ($payload['document_type'] ?? ''));
            $documentId = isset($payload['document_id']) ? (int) $payload['document_id'] : 0;
            $allocatedAmount = round((float) ($payload['allocated_amount'] ?? 0), 4);

            if ($paymentId < 1 || $documentType === '' || $documentId < 1 || $allocatedAmount <= 0) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'payment_id, document_type, document_id and allocated_amount are required.',
                ));
            }

            $payment = $this->paymentRepository->findById($paymentId);
            if (! $payment instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Payment not found.'));
            }

            if (strtolower((string) $payment->get('status', PaymentStatus::DRAFT)) === PaymentStatus::VOIDED) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Voided payments cannot be allocated.',
                ));
            }

            $tenantId = (int) ($payload['tenant_id'] ?? $payment->get('tenant_id', 0));
            if ($tenantId < 1 || $tenantId !== (int) $payment->get('tenant_id', 0)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Cross-tenant allocation is not allowed.',
                ));
            }

            $existing = $this->paymentAllocationRepository->list([
                'payment_id' => $paymentId,
                'document_type' => $documentType,
                'document_id' => $documentId,
            ]);
            if ($existing !== []) {
                return Result::failure(new Error(
                    PaymentErrorCode::CONFLICT,
                    'Allocation already exists for payment and document.',
                ));
            }

            return $this->paymentRepository->transaction(function () use (
                $payload,
                $payment,
                $paymentId,
                $documentType,
                $documentId,
                $allocatedAmount,
                $tenantId,
            ): Result {
                $currentTotal = $this->sumPaymentAllocations($paymentId);
                $paymentAmount = round((float) $payment->get('amount', 0), 4);
                if ($currentTotal + $allocatedAmount > $paymentAmount) {
                    return Result::failure(new Error(
                        PaymentErrorCode::INSUFFICIENT_UNALLOCATED_AMOUNT,
                        'Allocation amount exceeds unallocated payment amount.',
                    ));
                }

                $allocation = $this->paymentAllocationRepository->create([
                    'row_version' => (int) ($payload['row_version'] ?? 1),
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $payload['organization_unit_id'] ?? $payment->get('organization_unit_id'),
                    'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
                    'payment_id' => $paymentId,
                    'document_type' => $documentType,
                    'document_id' => $documentId,
                    'reference' => $payload['reference'] ?? null,
                    'allocated_amount' => $allocatedAmount,
                ]);

                $newTotal = round($currentTotal + $allocatedAmount, 4);
                $nextStatus = $newTotal >= $paymentAmount ? PaymentStatus::RECONCILED : PaymentStatus::POSTED;
                $this->paymentRepository->update($paymentId, [
                    'status' => $nextStatus,
                    'row_version' => ((int) $payment->get('row_version', 1)) + 1,
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
            $allocation = $this->paymentAllocationRepository->findById($id);
            if (! $allocation instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Payment allocation not found.'));
            }

            $payment = $this->paymentRepository->findById((int) $allocation->get('payment_id', 0));
            if (! $payment instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Payment not found.'));
            }

            if (strtolower((string) $payment->get('status', PaymentStatus::DRAFT)) === PaymentStatus::VOIDED) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Voided payments cannot be updated.',
                ));
            }

            if ($this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'payment_id, document_type and document_id are immutable for allocations.',
                ));
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

            return $this->paymentRepository->transaction(function () use (
                $allocation,
                $payment,
                $id,
                $payload,
                $newAmount,
            ): Result {
                $paymentId = (int) $allocation->get('payment_id', 0);
                $totalWithoutCurrent = $this->sumPaymentAllocations($paymentId, (int) $allocation->id());
                $paymentAmount = round((float) $payment->get('amount', 0), 4);

                if ($totalWithoutCurrent + $newAmount > $paymentAmount) {
                    return Result::failure(new Error(
                        PaymentErrorCode::INSUFFICIENT_UNALLOCATED_AMOUNT,
                        'Allocation amount exceeds unallocated payment amount.',
                    ));
                }

                $updated = $this->paymentAllocationRepository->update($id, array_merge($payload, [
                    'allocated_amount' => $newAmount,
                ]));

                $newTotal = round($totalWithoutCurrent + $newAmount, 4);
                $nextStatus = $newTotal >= $paymentAmount ? PaymentStatus::RECONCILED : PaymentStatus::POSTED;
                $this->paymentRepository->update($paymentId, [
                    'status' => $nextStatus,
                    'row_version' => ((int) $payment->get('row_version', 1)) + 1,
                ]);

                return Result::success($updated);
            });
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function sumPaymentAllocations(int $paymentId, ?int $excludingAllocationId = null): float
    {
        $sum = 0.0;
        $allocations = $this->paymentAllocationRepository->list(['payment_id' => $paymentId]);
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

    /** @param array<string, mixed> $payload */
    private function containsStructuralMutation(array $payload): bool
    {
        foreach (['payment_id', 'document_type', 'document_id'] as $field) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
