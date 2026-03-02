<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Domain\Enums;

enum StorefrontCartStatus: string
{
    case Active = 'active';
    case Converted = 'converted';
    case Abandoned = 'abandoned';
}
