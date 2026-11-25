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

enum BatchImportStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Imported = 'imported';
    case Failed = 'failed';
}
