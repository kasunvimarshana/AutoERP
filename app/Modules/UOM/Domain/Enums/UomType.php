<?php

declare(strict_types=1);

namespace Modules\UOM\Domain\Enums;

enum UomType: string
{
    case Unit = 'UNIT';
    case Mass = 'MASS';
    case Volume = 'VOLUME';
    case Length = 'LENGTH';
    case Time = 'TIME';
    case Other = 'OTHER';
}
