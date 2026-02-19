<?php

declare(strict_types=1);

namespace Modules\Reporting\Enums;

enum ReportType: string
{
    case SALES = 'sales';
    case FINANCIAL = 'financial';
    case INVENTORY = 'inventory';
    case CRM = 'crm';
    case PURCHASE = 'purchase';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::SALES => 'Sales Report',
            self::FINANCIAL => 'Financial Report',
            self::INVENTORY => 'Inventory Report',
            self::CRM => 'CRM Report',
            self::PURCHASE => 'Purchase Report',
            self::CUSTOM => 'Custom Report',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SALES => 'Reports for sales orders, invoices, and revenue',
            self::FINANCIAL => 'Financial statements and accounting reports',
            self::INVENTORY => 'Stock levels, movements, and valuations',
            self::CRM => 'Customer relationship and interaction reports',
            self::PURCHASE => 'Purchase orders and vendor analysis',
            self::CUSTOM => 'User-defined custom reports',
        };
    }
}
