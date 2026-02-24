<?php

namespace Modules\Currency\Domain\Enums;

enum RateSource: string
{
    case Manual    = 'manual';
    case Automatic = 'automatic';
}
