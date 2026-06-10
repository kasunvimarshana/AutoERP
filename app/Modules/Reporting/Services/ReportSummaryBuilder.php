<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Reporting\DTOs\ReportDefinition;

final class ReportSummaryBuilder
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{label: string, value: mixed, format: string}>
     */
    public function build(ReportDefinition $definition, array $rows): array
    {
        $summary = [[
            'label' => 'Rows',
            'value' => count($rows),
            'format' => 'integer',
        ]];

        foreach ($definition->columns as $column) {
            if (! $column->summarize) {
                continue;
            }

            $values = [];
            foreach ($rows as $row) {
                $value = $row[$column->key] ?? null;
                if ($value !== null && $value !== '' && preg_match('/^-?\d+(?:\.\d+)?$/', (string) $value)) {
                    $values[] = (string) $value;
                }
            }

            if ($values === []) {
                continue;
            }

            $summary[] = [
                'label' => 'Total '.$column->label,
                'value' => $this->math->sum($values),
                'format' => $column->format,
            ];
        }

        return $summary;
    }
}
