<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Validation\ValidationException;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleService\Models\VehicleServiceJob;

class VehicleServiceSalesSummaryExport
{
    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-service/sales-summary',
            title: 'Service Job Sales Summary',
            group: 'Vehicle Service',
            model: VehicleServiceJob::class,
            columns: [
                new ReportColumn('section', 'Section'),
                new ReportColumn('reference', 'Job / item'),
                new ReportColumn('quantity', 'Qty', format: 'decimal'),
                new ReportColumn('job_count', 'Jobs', format: 'integer'),
                new ReportColumn('revenue', 'Sales', format: 'money'),
                new ReportColumn('stock_cost', 'Stock cost', format: 'money'),
                new ReportColumn('direct_cost', 'Direct / component cost', format: 'money'),
                new ReportColumn('commission', 'Commission', format: 'money'),
                new ReportColumn('profit', 'Profit / loss', format: 'money'),
                new ReportColumn('collected', 'Collected', format: 'money'),
            ],
            description: 'Sales follow invoice date; collections follow payment date. Item profit is before commission; job profit is after direct costs and commission. Sections overlap and must not be added together.',
            orientation: 'landscape',
        );
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<int, array<string, mixed>>
     */
    public function rows(array $result): array
    {
        $summary = $result['summary'];
        $rows = [[
            'section' => 'Report total',
            'reference' => "Net margin: {$summary['margin']}%",
            'job_count' => $summary['job_count'],
            'revenue' => $summary['revenue'],
            'stock_cost' => $summary['stock_cost'],
            'direct_cost' => $summary['direct_cost'],
            'commission' => $summary['commission'],
            'profit' => $summary['profit'],
            'collected' => $summary['collected'],
        ]];

        foreach (['stock' => 'Stock performance', 'combo' => 'Combo performance'] as $key => $section) {
            foreach ($summary[$key]['items'] as $item) {
                $rows[] = [
                    'section' => $section,
                    'reference' => $this->itemLabel($item['item']),
                    'quantity' => $item['quantity'],
                    'job_count' => $item['job_count'],
                    'revenue' => $item['sales_amount'],
                    'stock_cost' => $key === 'stock' ? $item['cost'] : $item['stock_cost'],
                    'direct_cost' => $item['cost'],
                    'profit' => $item['profit'],
                ];
                foreach ($item['components'] ?? [] as $component) {
                    $rows[] = $this->componentRow($component, $this->itemLabel($item['item']));
                }
            }
        }

        foreach ($result['jobs'] as $job) {
            $rows[] = [
                'section' => 'Service job',
                'reference' => "{$job['job_number']} | {$job['date']} | {$job['customer']['name']} | {$job['vehicle']['name']}",
                'revenue' => $job['revenue'],
                'stock_cost' => $job['used_stock_cost'],
                'direct_cost' => $job['direct_cost'],
                'commission' => $job['commission'],
                'profit' => $job['profit'],
                'collected' => $job['collected'],
            ];
            foreach (['stock_items' => 'Job stock', 'combos' => 'Job combo', 'other_items' => 'Job service / labour'] as $key => $section) {
                foreach ($job[$key] as $item) {
                    $rows[] = [
                        'section' => $section,
                        'reference' => $job['job_number'].' | '.$this->itemLabel($item['item']),
                        'quantity' => $item['quantity'],
                        'revenue' => $item['sales_amount'],
                        'stock_cost' => $key === 'stock_items' ? $item['cost'] : ($item['stock_cost'] ?? null),
                        'direct_cost' => $item['component_cost'] ?? $item['cost'],
                    ];
                    foreach ($item['components'] ?? [] as $component) {
                        $rows[] = $this->componentRow($component, $job['job_number'].' | '.$this->itemLabel($item['item']));
                    }
                }
            }
        }

        $limit = (int) config('reporting.export_row_limit', 5000);
        if (count($rows) > $limit) {
            throw ValidationException::withMessages(['filters' => "Narrow the report to {$limit} export rows or fewer before exporting."]);
        }

        return $rows;
    }

    /** @param array<string, mixed> $component @return array<string, mixed> */
    private function componentRow(array $component, string $parent): array
    {
        return [
            'section' => $component['kind'] === 'stock' ? 'Combo stock component' : 'Combo service / labour component',
            'reference' => $parent.' > '.$this->itemLabel($component['item']),
            'quantity' => $component['quantity'],
            'stock_cost' => $component['kind'] === 'stock' ? $component['cost'] : null,
            'direct_cost' => $component['cost'],
        ];
    }

    /** @param array{code: string, name: string} $item */
    private function itemLabel(array $item): string
    {
        return $item['code'] === '' ? $item['name'] : "{$item['code']} — {$item['name']}";
    }
}
