<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\Events;

use Modules\Barcode\Domain\Entities\BarcodeScan;

/**
 * Dispatched after a barcode scan has been successfully recorded.
 *
 * Downstream modules (Inventory, Dispatch, Logistics, etc.) may listen
 * to this event to drive automated workflows such as stock receipt,
 * dispatch confirmation, or real-time location tracking.
 */
final class BarcodeScanRecorded
{
    public function __construct(
        public readonly BarcodeScan $scan,
    ) {}
}
