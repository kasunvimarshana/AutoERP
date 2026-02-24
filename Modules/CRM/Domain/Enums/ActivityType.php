<?php
namespace Modules\CRM\Domain\Enums;
enum ActivityType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Task = 'task';
    case Note = 'note';
    case Demo = 'demo';
}
