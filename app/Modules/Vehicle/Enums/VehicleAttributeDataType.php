<?php

declare(strict_types=1);

namespace Modules\Vehicle\Enums;

enum VehicleAttributeDataType: string
{
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Boolean = 'boolean';
    case Decimal = 'decimal';
}
