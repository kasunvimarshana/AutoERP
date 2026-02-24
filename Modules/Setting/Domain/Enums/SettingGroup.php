<?php
namespace Modules\Setting\Domain\Enums;
enum SettingGroup: string
{
    case Company = 'company';
    case Finance = 'finance';
    case Inventory = 'inventory';
    case Sales = 'sales';
    case Hr = 'hr';
    case Notification = 'notification';
    case Integration = 'integration';
}
