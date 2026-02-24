<?php
namespace Modules\CRM\Domain\Enums;
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Lost = 'lost';
    case Converted = 'converted';
}
