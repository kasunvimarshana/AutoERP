<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\Serials;

use Modules\Core\Application\Results\Result;

interface DeleteSerialServiceInterface
{
    public function execute(int|string $id): Result;
}