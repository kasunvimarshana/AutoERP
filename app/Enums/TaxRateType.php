<?php

namespace App\Enums;

enum TaxRateType: string
{
    case Simple = 'simple';
    case Compound = 'compound';
    case Group = 'group';
}
