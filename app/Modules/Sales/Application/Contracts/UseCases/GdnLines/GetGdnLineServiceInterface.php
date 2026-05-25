<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\GdnLines;

use Modules\Core\Application\Results\Result;

interface GetGdnLineServiceInterface
{
    public function execute(int|string $id): Result;
}