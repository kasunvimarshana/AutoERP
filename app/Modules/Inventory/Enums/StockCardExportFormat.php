<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum StockCardExportFormat: string
{
    case Csv = 'csv';
    case Excel = 'xls';
    case Pdf = 'pdf';
}
