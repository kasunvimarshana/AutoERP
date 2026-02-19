<?php

declare(strict_types=1);

namespace Modules\Reporting\Enums;

enum WidgetType: string
{
    case KPI = 'kpi';
    case CHART = 'chart';
    case TABLE = 'table';
    case SUMMARY = 'summary';
    case METRIC = 'metric';

    public function label(): string
    {
        return match ($this) {
            self::KPI => 'KPI Card',
            self::CHART => 'Chart Widget',
            self::TABLE => 'Table Widget',
            self::SUMMARY => 'Summary Widget',
            self::METRIC => 'Metric Widget',
        };
    }
}
