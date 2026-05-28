<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Finance\Application\Contracts\Services\FiscalPeriodServiceInterface;
use Modules\Finance\Application\Repositories\FiscalPeriodRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;

final class FiscalPeriodService implements FiscalPeriodServiceInterface
{
    public function __construct(private readonly FiscalPeriodRepositoryInterface $fiscalPeriods)
    {
    }

    public function requireOpenPeriod(int $tenantId, string $date, ?int $organizationUnitId = null): Result
    {
        $period = $this->fiscalPeriods->findOpenByDate($tenantId, $date, $organizationUnitId);

        if ($period === null) {
            return Result::failure(new Error(
                FinanceErrorCode::FISCAL_PERIOD_NOT_OPEN,
                'No open fiscal period found for posting date.',
                ['tenant_id' => $tenantId, 'date' => $date],
            ));
        }

        return Result::success($period);
    }
}
