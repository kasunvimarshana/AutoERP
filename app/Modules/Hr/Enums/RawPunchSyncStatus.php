<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Enums;

enum RawPunchSyncStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Duplicate = 'duplicate';
    case UnmappedUser = 'unmapped_user';
    case Failed = 'failed';
}
