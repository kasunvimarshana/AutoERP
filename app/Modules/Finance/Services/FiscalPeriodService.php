<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use InvalidArgumentException;
use Modules\Finance\Enums\FiscalPeriodStatus;
use Modules\Finance\Models\FinanceFiscalPeriod;

final class FiscalPeriodService
{
    public function assertOpen(?FinanceFiscalPeriod $period): void
    {
        if (! $period instanceof FinanceFiscalPeriod) {
            return;
        }

        $status = $period->status instanceof FiscalPeriodStatus
            ? $period->status
            : FiscalPeriodStatus::from((string) $period->status);

        if ($status !== FiscalPeriodStatus::Open) {
            throw new InvalidArgumentException('Cannot post into a closed or locked fiscal period.');
        }
    }
}
