<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Http\Response;
use Modules\Reporting\Contracts\ReportDataProvider;
use Modules\Reporting\DTOs\ReportData;

final class ReportService
{
    public function __construct(
        private readonly ReportDefinitionRegistry $definitions,
        private readonly ReportDataProvider $data,
        private readonly ReportDataFactory $documents,
        private readonly ReportExport $exports,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function index(): array
    {
        return $this->definitions->index();
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(string $key): array
    {
        return $this->definitions->get($key)->toArray();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function run(
        string $key,
        int $tenantId,
        ?int $organizationUnitId,
        array $input,
        int $perPage,
    ): array {
        $definition = $this->definitions->get($key);
        $page = $this->data->paginate($definition, $tenantId, $organizationUnitId, $input, $perPage);

        return [
            'data' => $this->data->formatRows($definition, $page->items()),
            'meta' => [
                'current_page' => $page->currentPage(),
                'from' => $page->firstItem(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'to' => $page->lastItem(),
                'total' => $page->total(),
            ],
            'report' => $definition->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function document(
        string $key,
        int $tenantId,
        ?int $organizationUnitId,
        array $input,
        string $mode = 'preview',
    ): ReportData {
        $definition = $this->definitions->get($key);
        $rowLimit = max(1, (int) config('reporting.export_row_limit'));
        $rows = $this->data->rows(
            $definition,
            $tenantId,
            $organizationUnitId,
            $input,
            $rowLimit + 1,
        );
        $truncated = count($rows) > $rowLimit;
        if ($truncated) {
            $rows = array_slice($rows, 0, $rowLimit);
        }

        return $this->documents->make(
            $definition,
            $rows,
            $tenantId,
            $organizationUnitId,
            $input,
            $mode,
            $rowLimit,
            $truncated,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function export(
        string $key,
        string $format,
        int $tenantId,
        ?int $organizationUnitId,
        array $input,
    ): Response {
        return $this->exports->response(
            $format,
            $this->document($key, $tenantId, $organizationUnitId, $input, $format),
        );
    }
}
