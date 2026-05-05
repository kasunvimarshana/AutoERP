<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\Asset;

interface UpdateAssetServiceInterface
{
    public function execute(int $tenantId, int $id, array $data): Asset;
}
