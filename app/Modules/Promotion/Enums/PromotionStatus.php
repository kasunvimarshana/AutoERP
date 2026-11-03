<?php

declare(strict_types=1);

namespace Modules\Promotion\Enums;

enum PromotionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Expired = 'expired';
    case Transferred = 'transferred';
}
