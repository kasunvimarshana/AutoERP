<?php

declare(strict_types=1);

namespace Modules\Item\Domain\Enums;

enum ItemType: string
{
    case Physical = 'PHYSICAL';
    case Service = 'SERVICE';
    case Digital = 'DIGITAL';
    case Combo = 'COMBO';
    case Variable = 'VARIABLE';
}
