<?php

namespace App\Modules\Reporting\Enums;

enum WidgetType: string
{
    case KPI = 'kpi';
    case CHART = 'chart';
    case TABLE = 'table';
    case RECENT_ACTIVITY = 'recent_activity';
    case QUICK_STATS = 'quick_stats';

    public function label(): string
    {
        return match ($this) {
            self::KPI => 'KPI Widget',
            self::CHART => 'Chart Widget',
            self::TABLE => 'Table Widget',
            self::RECENT_ACTIVITY => 'Recent Activity Widget',
            self::QUICK_STATS => 'Quick Stats Widget',
        };
    }
}
