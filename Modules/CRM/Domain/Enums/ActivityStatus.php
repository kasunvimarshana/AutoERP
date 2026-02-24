<?php
namespace Modules\CRM\Domain\Enums;
enum ActivityStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Cancelled = 'cancelled';
}
