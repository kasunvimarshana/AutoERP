<?php

declare(strict_types=1);

namespace Modules\Promotion\Enums;

enum PromotionCardAction: string
{
    case Issued = 'issued';
    case Transferred = 'transferred';
    case Paused = 'paused';
    case Reactivated = 'reactivated';
    case Replaced = 'replaced';
}
