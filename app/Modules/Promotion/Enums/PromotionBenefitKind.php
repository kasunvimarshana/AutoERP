<?php

declare(strict_types=1);

namespace Modules\Promotion\Enums;

enum PromotionBenefitKind: string
{
    case Direct = 'direct';
    case Earned = 'earned';
}
