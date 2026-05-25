<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Contracts\UseCases\Checks;

use Modules\Core\Application\Results\Result;

interface GetCheckServiceInterface
{
    public function execute(int|string $id): Result;
}