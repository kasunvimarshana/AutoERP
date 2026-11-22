<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Idempotency\Enums;

enum IdempotencyStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
