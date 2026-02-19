<?php

declare(strict_types=1);

namespace Modules\Reporting\Enums;

enum ReportFormat: string
{
    case TABLE = 'table';
    case CHART = 'chart';
    case SUMMARY = 'summary';
    case PIVOT = 'pivot';

    public function label(): string
    {
        return match ($this) {
            self::TABLE => 'Table',
            self::CHART => 'Chart',
            self::SUMMARY => 'Summary',
            self::PIVOT => 'Pivot Table',
        };
    }
}
