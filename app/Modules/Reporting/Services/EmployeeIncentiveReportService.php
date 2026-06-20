<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Support\Collection;
use Modules\Reporting\DTOs\ReportDefinition;

/**
 * Backward-compatible alias for the canonical Employee Commission report.
 */
final class EmployeeIncentiveReportService
{
    public function __construct(private readonly EmployeeCommissionReportService $commissions) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function run(array $params): array
    {
        $result = $this->commissions->run($this->normalize($params));
        $result['report'] = $this->definition()->toArray();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(array $params): Collection
    {
        return $this->commissions->exportRows($this->normalize($params));
    }

    public function definition(): ReportDefinition
    {
        $definition = $this->commissions->definition();

        return new ReportDefinition(
            key: 'vehicle-service/employee-incentives',
            title: 'Employee Incentive Report',
            group: $definition->group,
            model: $definition->model,
            columns: $definition->columns,
            dateColumn: $definition->dateColumn,
            defaultSort: $definition->defaultSort,
            defaultDirection: $definition->defaultDirection,
            description: 'Backward-compatible view of the canonical technician and supervisor Employee Commission Report.',
            orientation: $definition->orientation,
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function normalize(array $params): array
    {
        if (! empty($params['incentive_source']) && empty($params['commission_source'])) {
            $params['commission_source'] = $params['incentive_source'];
        }

        return $params;
    }
}
