<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\UseCases\InvoiceEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceEngines\TransitionInvoiceStatusServiceInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Domain\Constants\InvoiceErrorCode;
use Modules\Invoice\Domain\Constants\InvoiceStatus;
use Throwable;

final class TransitionInvoiceStatusService implements TransitionInvoiceStatusServiceInterface
{
    public function __construct(private readonly InvoiceRepositoryInterface $invoices)
    {
    }

    public function execute(int|string $invoiceId, array $payload): Result
    {
        try {
            $invoice = $this->invoices->findById($invoiceId);
            if (! $invoice instanceof DataRecord) {
                return Result::failure(new Error(InvoiceErrorCode::NOT_FOUND, 'Invoice not found.'));
            }

            $targetStatus = strtolower(trim((string) ($payload['target_status'] ?? '')));
            if (! InvoiceStatus::isValid($targetStatus)) {
                return Result::failure(new Error(InvoiceErrorCode::INVALID_VALUE, 'Invalid target status.'));
            }

            $currentStatus = strtolower(trim((string) $invoice->get('status', InvoiceStatus::DRAFT)));
            if (! InvoiceStatus::isValid($currentStatus)) {
                $currentStatus = InvoiceStatus::DRAFT;
            }

            if (! InvoiceStatus::canTransition($currentStatus, $targetStatus)) {
                return Result::failure(new Error(
                    InvoiceErrorCode::INVALID_STATUS_TRANSITION,
                    'Invalid invoice status transition.',
                    [
                        'from' => $currentStatus,
                        'to' => $targetStatus,
                    ],
                ));
            }

            $expectedRowVersion = isset($payload['expected_row_version'])
                ? (int) $payload['expected_row_version']
                : null;

            $currentRowVersion = (int) $invoice->get('row_version', 1);
            if ($expectedRowVersion !== null && $expectedRowVersion !== $currentRowVersion) {
                return Result::failure(new Error(
                    InvoiceErrorCode::INVALID_VALUE,
                    'Invoice row version mismatch.',
                    [
                        'expected_row_version' => $expectedRowVersion,
                        'current_row_version' => $currentRowVersion,
                    ],
                ));
            }

            $grandTotal = (float) $invoice->get('grand_total', 0);
            $paidAmount = (float) $invoice->get('paid_amount', 0);

            if ($targetStatus === InvoiceStatus::PAID && round($paidAmount, 4) < round($grandTotal, 4)) {
                return Result::failure(new Error(
                    InvoiceErrorCode::INVALID_VALUE,
                    'Invoice cannot move to paid while balance remains.',
                ));
            }

            if (
                $targetStatus === InvoiceStatus::PARTIALLY_PAID
                && (round($paidAmount, 4) <= 0.0 || round($paidAmount, 4) >= round($grandTotal, 4))
            ) {
                return Result::failure(new Error(
                    InvoiceErrorCode::INVALID_VALUE,
                    'Invoice can be partially paid only when paid amount is between zero and grand total.',
                ));
            }

            $updated = $this->invoices->update((int) $invoice->id(), [
                'status' => $targetStatus,
                'row_version' => $currentRowVersion + 1,
            ]);

            return Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InvoiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
