<?php

declare(strict_types=1);

namespace Modules\Promotion\Enums;

enum PromotionEventType: string
{
    case Qualification = 'qualification';
    case Redemption = 'redemption';
    case Correction = 'correction';
}
