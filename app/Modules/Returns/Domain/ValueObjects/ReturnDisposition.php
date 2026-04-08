<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\ValueObjects;

class ReturnDisposition
{
    const RESTOCK       = 'restock';
    const SCRAP         = 'scrap';
    const VENDOR_RETURN = 'vendor_return';
    const QUARANTINE    = 'quarantine';

    public static function values(): array
    {
        return ['restock', 'scrap', 'vendor_return', 'quarantine'];
    }
}
