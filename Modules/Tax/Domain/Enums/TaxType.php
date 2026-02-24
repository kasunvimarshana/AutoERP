<?php

namespace Modules\Tax\Domain\Enums;

enum TaxType: string
{
    case Percentage = 'percentage';
    case Fixed      = 'fixed';
}
