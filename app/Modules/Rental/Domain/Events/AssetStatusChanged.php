<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Events;

use Modules\Rental\Domain\Entities\Asset;

class AssetStatusChanged
{
    public function __construct(
        public readonly Asset $asset,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly string $statusType,
    ) {}
}
