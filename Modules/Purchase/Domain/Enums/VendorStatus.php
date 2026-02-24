<?php
namespace Modules\Purchase\Domain\Enums;
enum VendorStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Blacklisted = 'blacklisted';
}
