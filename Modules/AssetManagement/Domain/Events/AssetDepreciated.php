<?php

namespace Modules\AssetManagement\Domain\Events;

class AssetDepreciated
{
    public function __construct(
        public readonly string $assetId,
        public readonly string $tenantId,
        public readonly string $depreciationAmount = '0',
        public readonly string $bookValueAfter = '0',
        public readonly string $assetName = '',
        public readonly string $periodLabel = '',
    ) {}
}
