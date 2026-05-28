<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\Services\PaymentServiceInterface;
use Modules\Payment\Application\Repositories\PaymentMethodRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Modules\Payment\Domain\Constants\PaymentStatus;
use Throwable;

final class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {
    }

    public function createPayment(array $payload): Result
    {
        try {
            $validation = $this->validateCreatePayload($payload);
            if ($validation->isFailure()) {
                return $validation;
            }

            $tenantId = (int) $payload['tenant_id'];
            $idempotencyKey = isset($payload['idempotency_key']) ? trim((string) $payload['idempotency_key']) : '';
            if ($idempotencyKey !== '') {
                $existing = $this->findByIdempotency($tenantId, $idempotencyKey);
                if ($existing instanceof DataRecord) {
                    return Result::success($existing);
                }
            }

            $amount = round((float) $payload['amount'], 4);
            $exchangeRate = round((float) ($payload['exchange_rate'] ?? 1), 4);
            $payload['base_amount'] = round((float) ($payload['base_amount'] ?? ($amount * $exchangeRate)), 4);
            $payload['amount'] = $amount;
            $payload['exchange_rate'] = $exchangeRate;
            $payload['status'] = $payload['status'] ?? PaymentStatus::DRAFT;
            $payload['direction'] = strtolower(trim((string) ($payload['direction'] ?? 'inbound')));
            $payload['row_version'] = (int) ($payload['row_version'] ?? 1);

            return Result::success($this->paymentRepository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function updatePayment(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->paymentRepository->findById($id);
            if (! $existing instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Payment not found.'));
            }

            $status = strtolower(trim((string) $existing->get('status', PaymentStatus::DRAFT)));
            if ($this->isImmutableStatus($status) && $this->containsStructuralMutation($payload)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Posted/reconciled/voided payments are immutable for structural fields.',
                ));
            }

            $merged = array_merge($existing->toArray(), $payload);
            $validation = $this->validateUpdatePayload($merged, $existing, $payload);
            if ($validation->isFailure()) {
                return $validation;
            }

            if (array_key_exists('amount', $payload) || array_key_exists('exchange_rate', $payload)) {
                $amount = round((float) $merged['amount'], 4);
                $exchangeRate = round((float) $merged['exchange_rate'], 4);
                if (! array_key_exists('base_amount', $payload)) {
                    $payload['base_amount'] = round($amount * $exchangeRate, 4);
                }
            }

            return Result::success($this->paymentRepository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /** @param array<string, mixed> $payload */
    private function validateCreatePayload(array $payload): Result
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
        $paymentMethodId = isset($payload['payment_method_id']) ? (int) $payload['payment_method_id'] : 0;
        $paymentNumber = trim((string) ($payload['payment_number'] ?? ''));
        $paymentDate = trim((string) ($payload['payment_date'] ?? ''));
        $amount = (float) ($payload['amount'] ?? 0);
        $accountId = isset($payload['account_id']) ? (int) $payload['account_id'] : 0;
        $direction = strtolower(trim((string) ($payload['direction'] ?? 'inbound')));
        $exchangeRate = (float) ($payload['exchange_rate'] ?? 1);

        if (
            $tenantId < 1
            || $paymentMethodId < 1
            || $paymentNumber === ''
            || $paymentDate === ''
            || $amount <= 0
            || $accountId < 1
        ) {
            return Result::failure(new Error(
                PaymentErrorCode::INVALID_VALUE,
                'tenant_id, payment_method_id, payment_number, payment_date, amount and account_id are required.',
            ));
        }

        if (! in_array($direction, ['inbound', 'outbound'], true)) {
            return Result::failure(new Error(
                PaymentErrorCode::INVALID_VALUE,
                'direction must be inbound or outbound.',
            ));
        }

        if ($exchangeRate <= 0) {
            return Result::failure(new Error(
                PaymentErrorCode::INVALID_VALUE,
                'exchange_rate must be greater than zero.',
            ));
        }

        $method = $this->paymentMethodRepository->findById($paymentMethodId);
        if (! $method instanceof DataRecord || (int) $method->get('tenant_id', 0) !== $tenantId) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, 'Invalid payment method for tenant.'));
        }

        if (! (bool) $method->get('is_active', false)) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, 'Payment method is not active.'));
        }

        return Result::success(true);
    }

    /**
     * @param array<string, mixed> $merged
     * @param array<string, mixed> $payload
     */
    private function validateUpdatePayload(array $merged, DataRecord $existing, array $payload): Result
    {
        $validation = $this->validateCreatePayload($merged);
        if ($validation->isFailure()) {
            return $validation;
        }

        if (array_key_exists('idempotency_key', $payload)) {
            $key = trim((string) $payload['idempotency_key']);
            if ($key !== '') {
                $conflict = $this->findByIdempotency((int) $existing->get('tenant_id'), $key);
                if ($conflict instanceof DataRecord && $conflict->id() !== $existing->id()) {
                    return Result::failure(new Error(
                        PaymentErrorCode::CONFLICT,
                        'idempotency_key already exists for this tenant.',
                    ));
                }
            }
        }

        return Result::success(true);
    }

    private function findByIdempotency(int $tenantId, string $idempotencyKey): ?DataRecord
    {
        $matches = $this->paymentRepository->list([
            'tenant_id' => $tenantId,
            'idempotency_key' => $idempotencyKey,
        ]);

        foreach ($matches as $match) {
            if ($match instanceof DataRecord) {
                return $match;
            }
        }

        return null;
    }

    private function isImmutableStatus(string $status): bool
    {
        return in_array($status, [PaymentStatus::POSTED, PaymentStatus::RECONCILED, PaymentStatus::VOIDED], true);
    }

    /** @param array<string, mixed> $payload */
    private function containsStructuralMutation(array $payload): bool
    {
        foreach (
            [
                'tenant_id',
                'organization_unit_id',
                'party_type',
                'party_id',
                'payment_number',
                'payment_date',
                'amount',
                'direction',
                'payment_method_id',
                'account_id',
                'currency_id',
                'exchange_rate',
                'base_amount',
                'idempotency_key',
            ] as $field
        ) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }
}
