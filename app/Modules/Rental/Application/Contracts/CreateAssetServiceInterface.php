<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\Asset;

interface CreateAssetServiceInterface
{
    public function execute(array $data): Asset;
}
