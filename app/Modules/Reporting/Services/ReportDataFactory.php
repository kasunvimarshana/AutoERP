<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use DateTimeImmutable;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\DTOs\ReportDefinition;

final class ReportDataFactory
{
    public function __construct(
        private readonly ReportBrandingResolver $branding,
        private readonly ReportSummaryBuilder $summaries,
        private readonly ReportTemplateResolver $templates,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $input
     */
    public function make(
        ReportDefinition $definition,
        array $rows,
        int $tenantId,
        ?int $organizationUnitId,
        array $input = [],
        string $mode = 'preview',
    ): ReportData {
        return new ReportData(
            definition: $definition,
            rows: $rows,
            summary: $this->summaries->build($definition, $rows),
            branding: $this->branding->resolve($tenantId, $organizationUnitId),
            filters: $this->filters($input),
            generatedAt: new DateTimeImmutable,
            template: $this->templates->resolve($definition),
            mode: $mode,
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, array{label: string, value: string}>
     */
    private function filters(array $input): array
    {
        $excluded = ['tenant_id', 'organization_unit_id', 'page', 'per_page', 'direction'];
        $filters = [];

        foreach ($input as $key => $value) {
            if (in_array((string) $key, $excluded, true) || $value === null || $value === '' || $value === []) {
                continue;
            }

            if ($key === 'filters' && is_array($value)) {
                foreach ($value as $filterKey => $filterValue) {
                    if ($filterValue !== null && $filterValue !== '') {
                        $filters[] = [
                            'label' => $this->label((string) $filterKey),
                            'value' => $this->plain($filterValue),
                        ];
                    }
                }

                continue;
            }

            $filters[] = [
                'label' => $this->label((string) $key),
                'value' => $this->plain($value),
            ];
        }

        return $filters;
    }

    private function label(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    private function plain(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn (mixed $item): string => $this->plain($item), $value));
        }

        return (string) $value;
    }
}
