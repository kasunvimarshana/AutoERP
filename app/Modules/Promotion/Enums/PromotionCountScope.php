<?php

declare(strict_types=1);

namespace Modules\Promotion\Enums;

enum PromotionCountScope: string
{
    case Customer = 'customer';
    case Vehicle = 'vehicle';
}
