<?php

namespace Modules\AssetManagement\Domain\Events;

class AssetDisposed
{
    public function __construct(
        public readonly string $assetId,
        public readonly string $tenantId,
    ) {}
}
