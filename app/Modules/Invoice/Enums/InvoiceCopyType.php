<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Enums;

enum InvoiceCopyType: string
{
    case Original = 'original';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
