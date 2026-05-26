<?php

declare(strict_types=1);

namespace Modules\Payment\Application\UseCases\PaymentEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\PaymentEngines\SettlePaymentStatusServiceInterface;
use Modules\Payment\Application\Repositories\PaymentAllocationRepositoryInterface;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Domain\Constants\PaymentErrorCode;
use Modules\Payment\Domain\Constants\PaymentStatus;
use Throwable;

final class SettlePaymentStatusService implements SettlePaymentStatusServiceInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly PaymentAllocationRepositoryInterface $allocations,
    ) {
    }

    public function execute(int|string $paymentId, array $payload): Result
    {
        try {
            $payment = $this->payments->findById($paymentId);
            if (! $payment instanceof DataRecord) {
                return Result::failure(new Error(PaymentErrorCode::NOT_FOUND, 'Payment not found.'));
            }

            $targetStatus = strtolower(trim((string) ($payload['target_status'] ?? '')));
            if (! PaymentStatus::isValid($targetStatus)) {
                return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, 'Invalid target status.'));
            }

            $currentStatus = strtolower(trim((string) $payment->get('status', PaymentStatus::DRAFT)));
            if (! PaymentStatus::isValid($currentStatus)) {
                $currentStatus = PaymentStatus::DRAFT;
            }

            if (! PaymentStatus::canTransition($currentStatus, $targetStatus)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_STATUS_TRANSITION,
                    'Invalid payment status transition.',
                    ['from' => $currentStatus, 'to' => $targetStatus],
                ));
            }

            $expectedRowVersion = isset($payload['expected_row_version'])
                ? (int) $payload['expected_row_version']
                : null;
            $currentRowVersion = (int) $payment->get('row_version', 1);

            if ($expectedRowVersion !== null && $expectedRowVersion !== $currentRowVersion) {
                return Result::failure(new Error(
                    PaymentErrorCode::CONFLICT,
                    'Payment row version mismatch.',
                    [
                        'expected_row_version' => $expectedRowVersion,
                        'current_row_version' => $currentRowVersion,
                    ],
                ));
            }

            $allocations = $this->allocations->list(['payment_id' => (int) $payment->id()]);
            $allocatedTotal = $this->sumAllocated($allocations);
            $paymentAmount = (float) $payment->get('amount', 0);

            if ($targetStatus === PaymentStatus::RECONCILED && $allocatedTotal < round($paymentAmount, 4)) {
                return Result::failure(new Error(
                    PaymentErrorCode::INSUFFICIENT_UNALLOCATED_AMOUNT,
                    'Payment cannot be reconciled until allocated total reaches payment amount.',
                ));
            }

            if ($targetStatus === PaymentStatus::VOIDED && $allocatedTotal > 0) {
                return Result::failure(new Error(
                    PaymentErrorCode::INVALID_VALUE,
                    'Allocated payments cannot be voided. Unallocate first.',
                ));
            }

            $updated = $this->payments->update((int) $payment->id(), [
                'status' => $targetStatus,
                'row_version' => $currentRowVersion + 1,
            ]);

            return Result::success([
                'payment' => $updated->toArray(),
                'allocated_total' => $allocatedTotal,
                'payment_amount' => round($paymentAmount, 4),
            ]);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PaymentErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param list<DataRecord> $allocations
     */
    private function sumAllocated(array $allocations): float
    {
        $sum = 0.0;
        foreach ($allocations as $allocation) {
            if (! $allocation instanceof DataRecord) {
                continue;
            }

            $sum += (float) $allocation->get('allocated_amount', 0);
        }

        return round($sum, 4);
    }
}
