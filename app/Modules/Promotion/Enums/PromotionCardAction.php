<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Promotion\Enums;

enum PromotionCardAction: string
{
    case Issued = 'issued';
    case Transferred = 'transferred';
    case Paused = 'paused';
    case Reactivated = 'reactivated';
    case Replaced = 'replaced';
    case OwnershipEnded = 'ownership_ended';
    case Exhausted = 'exhausted';
    case UsesRestored = 'uses_restored';
}
