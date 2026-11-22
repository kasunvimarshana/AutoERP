<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Purchase\Enums;

enum PurchaseDebitNoteStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Posted = 'posted';
}
