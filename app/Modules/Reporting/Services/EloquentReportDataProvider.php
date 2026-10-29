<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Reporting\Contracts\ReportDataProvider;
use Modules\Reporting\DTOs\ReportDefinition;

final class EloquentReportDataProvider implements ReportDataProvider
{
    public function __construct(private readonly ReportQueryBuilder $queries) {}

    public function paginate(
        ReportDefinition $definition,
        int $tenantId,
        ?int $organizationUnitId,
        array $input,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->queries->paginate($definition, $tenantId, $organizationUnitId, $input, $perPage);
    }

    public function rows(
        ReportDefinition $definition,
        int $tenantId,
        ?int $organizationUnitId,
        array $input,
        int $limit,
    ): array {
        return $this->formatRows(
            $definition,
            $this->queries->query($definition, $tenantId, $organizationUnitId, $input)->limit($limit)->get(),
        );
    }

    public function formatRows(ReportDefinition $definition, iterable $rows): array
    {
        $models = is_array($rows) ? $rows : iterator_to_array($rows);
        if ($definition->prepareRows !== null) {
            ($definition->prepareRows)($models);
        }

        return $this->queries->rows($definition, $models);
    }
}
