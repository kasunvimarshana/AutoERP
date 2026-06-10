<?php

declare(strict_types=1);

namespace Modules\Reporting\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Reporting\DTOs\ReportDefinition;

interface ReportDataProvider
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function paginate(
        ReportDefinition $definition,
        int $tenantId,
        ?int $organizationUnitId,
        array $input,
        int $perPage,
    ): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, array<string, mixed>>
     */
    public function rows(
        ReportDefinition $definition,
        int $tenantId,
        ?int $organizationUnitId,
        array $input,
        int $limit,
    ): array;

    /**
     * @param  iterable<mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function formatRows(ReportDefinition $definition, iterable $rows): array;
}
