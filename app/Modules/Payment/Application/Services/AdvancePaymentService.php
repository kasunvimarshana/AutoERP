<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\AdvancePaymentServiceInterface;
use Modules\Payment\Application\Repositories\AdvancePaymentRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Throwable;

final class AdvancePaymentService implements AdvancePaymentServiceInterface
{
    public function __construct(
        private readonly AdvancePaymentRepositoryInterface $advancePaymentRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    public function createAdvance(array $payload): Result
    {
        try {
            $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
            $partyType = trim((string) ($payload['party_type'] ?? ''));
            $partyId = isset($payload['party_id']) ? (int) $payload['party_id'] : 0;
            $advanceNumber = trim((string) ($payload['advance_number'] ?? ''));
            $amount = round((float) ($payload['amount'] ?? 0), 4);
            $advanceDate = trim((string) ($payload['advance_date'] ?? ''));

            if (
                $tenantId < 1
                || $partyType === ''
                || $partyId < 1
                || $advanceNumber === ''
                || $amount <= 0
                || $advanceDate === ''
            ) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'tenant_id, party_type, party_id, advance_number, advance_date and amount are required.',
                ));
            }

            $paymentId = isset($payload['payment_id']) ? (int) $payload['payment_id'] : null;
            if ($paymentId !== null) {
                $payment = $this->paymentRepository->findById($paymentId);
                if (! $payment instanceof DataRecord || (int) $payment->get('tenant_id', 0) !== $tenantId) {
                    return Result::failure(new Error(
                        PaymentErrorCode::INVALID_VALUE,
                        'Invalid linked payment for tenant.',
                    ));
                }
            }

            $payload['row_version'] = (int) ($payload['row_version'] ?? 1);
            $payload['amount'] = $amount;
            $payload['remaining_amount'] = round((float) ($payload['remaining_amount'] ?? $amount), 4);

            if ($payload['remaining_amount'] < 0 || $payload['remaining_amount'] > $amount) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'remaining_amount must be between 0 and amount.',
                ));
            }

            $payload['status'] = $this->resolveAdvanceStatus(
                (float) $payload['remaining_amount'],
                $amount,
                (string) ($payload['status'] ?? ''),
            );

            return Result::success($this->advancePaymentRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updateAdvance(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->advancePaymentRepository->findById($id);
            if (! $existing instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Advance payment not found.'));
            }

            if ($this->containsImmutableMutation($payload)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'advance_number, amount, remaining_amount, status and '
                    . 'payment linkage are immutable through update.',
                ));
            }

            $status = strtolower((string) $existing->get('status', 'open'));
            if (in_array($status, ['fully_applied', 'refunded', 'cancelled'], true)) {
                $allowed = $this->payloadSubset($payload, ['notes', 'metadata']);
                if (count($allowed) !== count($payload)) {
                    return Result::failure(new Error(
                        PaymentErrorCode::INVALID_VALUE,
                        'Closed advances allow only notes and metadata updates.',
                    ));
                }
            }

            return Result::success($this->advancePaymentRepository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function resolveAdvanceStatus(float $remainingAmount, float $amount, string $status): string
    {
        $normalized = strtolower(trim($status));
        if (in_array($normalized, ['cancelled', 'refunded'], true)) {
            return $normalized;
        }

        if ($remainingAmount <= 0) {
            return 'fully_applied';
        }

        if ($remainingAmount < $amount) {
            return 'partially_applied';
        }

        return 'open';
    }

    /** @param array<string, mixed> $payload */
    private function containsImmutableMutation(array $payload): bool
    {
        $immutableFields = [
            'tenant_id',
            'party_type',
            'party_id',
            'advance_number',
            'amount',
            'remaining_amount',
            'status',
            'payment_id',
        ];

        foreach ($immutableFields as $field) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $allowedFields
     * @return array<string, mixed>
     */
    private function payloadSubset(array $payload, array $allowedFields): array
    {
        $subset = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $payload)) {
                $subset[$field] = $payload[$field];
            }
        }

        return $subset;
    }
}
