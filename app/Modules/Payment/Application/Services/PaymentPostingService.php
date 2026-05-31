<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Services;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Core\Application\Contracts\ClockInterface;
use Modules\Payment\Application\Contracts\Services\PaymentPostingServiceInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Modules\Payment\Domain\Constants\PaymentStatus;
use Throwable;

final class PaymentPostingService implements PaymentPostingServiceInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function postPayment(int|string $paymentId, array $payload = []): Result
    {
        try {
            $payment = $this->paymentRepository->findById($paymentId);
            if (! $payment instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Payment not found.'));
            }

            $status = strtolower((string) $payment->get('status', PaymentStatus::DRAFT));
            if (
                $status === PaymentStatus::VOIDED
                || $status === PaymentStatus::REVERSED
            ) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Voided or reversed payments cannot be posted.',
                ));
            }

            if (in_array($status, [PaymentStatus::POSTED, PaymentStatus::RECONCILED], true)) {
                return Result::success($payment);
            }

            $expectedRowVersion = isset($payload['expected_row_version'])
                ? (int) $payload['expected_row_version']
                : null;
            $currentRowVersion = (int) $payment->get('row_version', 1);
            if ($expectedRowVersion !== null && $expectedRowVersion !== $currentRowVersion) {
                return Result::failure(new Error(
                    PaymentErrorCode::CONFLICT,
                    'Payment row version mismatch.',
                ));
            }

            $updated = $this->paymentRepository->update($paymentId, [
                'status' => PaymentStatus::POSTED,
                'posted_by' => $payload['posted_by'] ?? null,
                'posted_at' => $payload['posted_at'] ?? $this->clock->now()->format('Y-m-d H:i:s'),
                'row_version' => $currentRowVersion + 1,
            ]);

            return Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
