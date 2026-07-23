<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Reporting\DTOs\ReportDefinition;

final class VehicleRentalReportService
{
    public const RUNNING_CHART = 'vehicle-rental/running-chart';
    public const CHART_EXCEPTIONS = 'vehicle-rental/chart-exceptions';
    public const CUSTOMER_INVOICES = 'vehicle-rental/customer-invoices';
    public const OWNER_VOUCHERS = 'vehicle-rental/owner-vouchers';
    public const RENTAL_HISTORY = 'vehicle-rental/rental-history';

    public function __construct(
        private readonly VehicleRentalReportDefinitions $definitions,
        private readonly VehicleRentalOperationalReportService $operations,
        private readonly VehicleRentalFinancialReportService $financial,
        private readonly VehicleRentalChartExceptionReportService $exceptions,
    ) {}

    /** @return list<ReportDefinition> */
    public function definitions(): array
    {
        return $this->definitions->all();
    }

    public function definition(string $key): ReportDefinition
    {
        return $this->definitions->get($key);
    }

    /** @param array<string, mixed> $params @return array<string, mixed> */
    public function run(string $key, array $params): array
    {
        return match ($key) {
            self::RUNNING_CHART, self::RENTAL_HISTORY => $this->operations->run($key, $params, $this->definition($key)),
            self::CUSTOMER_INVOICES, self::OWNER_VOUCHERS => $this->financial->run($key, $params, $this->definition($key)),
            self::CHART_EXCEPTIONS => $this->exceptions->run($params, $this->definition($key)),
            default => throw new InvalidArgumentException("Vehicle Rental report [{$key}] is not defined."),
        };
    }

    /** @param array<string, mixed> $params @return Collection<int, array<string, mixed>> */
    public function exportRows(string $key, array $params): Collection
    {
        return match ($key) {
            self::RUNNING_CHART, self::RENTAL_HISTORY => $this->operations->exportRows($key, $params),
            self::CUSTOMER_INVOICES, self::OWNER_VOUCHERS => $this->financial->exportRows($key, $params),
            self::CHART_EXCEPTIONS => $this->exceptions->exportRows($params),
            default => throw new InvalidArgumentException("Vehicle Rental report [{$key}] is not defined."),
        };
    }
}
