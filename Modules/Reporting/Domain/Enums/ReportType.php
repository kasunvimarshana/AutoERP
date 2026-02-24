<?php

namespace Modules\Reporting\Domain\Enums;

enum ReportType: string
{
    case Sales       = 'sales';
    case Purchase    = 'purchase';
    case Inventory   = 'inventory';
    case Accounting  = 'accounting';
    case HR          = 'hr';
    case POS         = 'pos';
    case CRM         = 'crm';
    case Project     = 'project';
    case Custom      = 'custom';
}
