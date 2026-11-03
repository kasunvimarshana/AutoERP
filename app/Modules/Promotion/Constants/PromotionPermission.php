<?php

declare(strict_types=1);

namespace Modules\Promotion\Constants;

final class PromotionPermission
{
    public const VIEW = 'promotion.view';

    public const MANAGE = 'promotion.manage';

    public const REDEEM = 'promotion.redeem';

    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View offers, service cards, and customer progress.',
            self::MANAGE => 'Create, issue, pause, transfer, and retire service offers and cards.',
            self::REDEEM => 'Assign an eligible offer and apply a benefit to a vehicle service job.',
        ];
    }
}
