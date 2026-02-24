<?php

namespace Modules\Reporting\Domain\Enums;

enum WidgetType: string
{
    case KpiCard   = 'kpi_card';
    case BarChart  = 'bar_chart';
    case LineChart = 'line_chart';
    case PieChart  = 'pie_chart';
    case Funnel    = 'funnel';
    case DataTable = 'data_table';
    case Text      = 'text';
}
