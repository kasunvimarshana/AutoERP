<?php

declare(strict_types=1);

namespace Modules\Reporting\Enums;

enum ChartType: string
{
    case LINE = 'line';
    case BAR = 'bar';
    case PIE = 'pie';
    case AREA = 'area';
    case SCATTER = 'scatter';
    case DONUT = 'donut';

    public function label(): string
    {
        return match ($this) {
            self::LINE => 'Line Chart',
            self::BAR => 'Bar Chart',
            self::PIE => 'Pie Chart',
            self::AREA => 'Area Chart',
            self::SCATTER => 'Scatter Plot',
            self::DONUT => 'Donut Chart',
        };
    }
}
