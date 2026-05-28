<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Carbon\CarbonImmutable;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\Services\PaymentTermServiceInterface;
use Modules\Finance\Application\Repositories\PaymentTermRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;

final class PaymentTermService implements PaymentTermServiceInterface
{
    public function __construct(private readonly PaymentTermRepositoryInterface $paymentTerms)
    {
    }

    public function calculateDueDate(int $paymentTermId, int $tenantId, string $baseDate): Result
    {
        $term = $this->paymentTerms->findById($paymentTermId);

        if ($term === null || (int) $term->get('tenant_id') !== $tenantId) {
            return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Payment term not found for tenant.'));
        }

        if (! (bool) $term->get('is_active', false)) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, 'Payment term is inactive.'));
        }

        $resolvedBaseDate = CarbonImmutable::parse($baseDate);
        $paymentType = strtolower((string) $term->get('payment_type', 'net'));
        $dueDays = (int) $term->get('due_days', 0);

        $dueDate = match ($paymentType) {
            'end_of_month' => $resolvedBaseDate->endOfMonth(),
            'end_of_next_month' => $resolvedBaseDate->addMonthNoOverflow()->endOfMonth(),
            default => $resolvedBaseDate->addDays($dueDays),
        };

        return Result::success($dueDate->toDateString());
    }
}
