<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\GrnHeaders;

use Modules\Core\Application\Results\Result;

interface DeleteGrnHeaderServiceInterface
{
    public function execute(int|string $id): Result;
}