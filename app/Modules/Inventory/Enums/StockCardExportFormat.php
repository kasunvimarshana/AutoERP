<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Enums;

enum StockCardExportFormat: string
{
    case Csv = 'csv';
    case Excel = 'xls';
    case Pdf = 'pdf';
}
