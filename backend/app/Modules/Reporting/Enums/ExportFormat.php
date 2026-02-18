<?php

namespace App\Modules\Reporting\Enums;

enum ExportFormat: string
{
    case CSV = 'csv';
    case PDF = 'pdf';
    case EXCEL = 'excel';
    case JSON = 'json';

    public function label(): string
    {
        return match ($this) {
            self::CSV => 'CSV',
            self::PDF => 'PDF',
            self::EXCEL => 'Excel',
            self::JSON => 'JSON',
        };
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::CSV => 'text/csv',
            self::PDF => 'application/pdf',
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::JSON => 'application/json',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::CSV => 'csv',
            self::PDF => 'pdf',
            self::EXCEL => 'xlsx',
            self::JSON => 'json',
        };
    }
}
