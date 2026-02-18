<?php

namespace App\Modules\Reporting\Enums;

enum ReportType: string
{
    case TABLE = 'table';
    case CHART = 'chart';
    case KPI = 'kpi';
    case EXPORT = 'export';

    public function label(): string
    {
        return match ($this) {
            self::TABLE => 'Table Report',
            self::CHART => 'Chart Report',
            self::KPI => 'KPI Report',
            self::EXPORT => 'Export Report',
        };
    }

    public function supportsVisualization(): bool
    {
        return in_array($this, [self::CHART, self::KPI]);
    }
}
