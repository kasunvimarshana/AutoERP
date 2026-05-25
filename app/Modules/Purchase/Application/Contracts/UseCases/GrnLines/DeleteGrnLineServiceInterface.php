<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\GrnLines;

use Modules\Core\Application\Results\Result;

interface DeleteGrnLineServiceInterface
{
    public function execute(int|string $id): Result;
}