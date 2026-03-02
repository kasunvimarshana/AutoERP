<?php

declare(strict_types=1);

namespace Modules\Crm\Domain\Enums;

enum ActivityType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Note = 'note';
    case Task = 'task';
}
