<?php

declare(strict_types=1);

namespace Modules\Asset\Application\Contracts;

interface SyncAssetAvailabilityServiceInterface
{
    public function execute(array $data = []): mixed;
}
