<?php

declare(strict_types=1);

namespace Modules\Reporting\Enums;

enum ExportFormat: string
{
    case CSV = 'csv';
    case JSON = 'json';
    case PDF = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::CSV => 'CSV',
            self::JSON => 'JSON',
            self::PDF => 'PDF',
        };
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::CSV => 'text/csv',
            self::JSON => 'application/json',
            self::PDF => 'application/pdf',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::CSV => 'csv',
            self::JSON => 'json',
            self::PDF => 'pdf',
        };
    }
}
