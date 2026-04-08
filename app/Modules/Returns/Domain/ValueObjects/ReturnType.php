<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\ValueObjects;

class ReturnType
{
    const PURCHASE_RETURN = 'purchase_return';
    const SALES_RETURN    = 'sales_return';

    public static function values(): array
    {
        return ['purchase_return', 'sales_return'];
    }
}
