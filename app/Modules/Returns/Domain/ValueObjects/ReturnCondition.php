<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\ValueObjects;

class ReturnCondition
{
    const GOOD      = 'good';
    const DAMAGED   = 'damaged';
    const DEFECTIVE = 'defective';
    const EXPIRED   = 'expired';

    public static function values(): array
    {
        return ['good', 'damaged', 'defective', 'expired'];
    }
}
