<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\GdnHeaders;

use Modules\Core\Application\Results\Result;

interface GetGdnHeaderServiceInterface
{
    public function execute(int|string $id): Result;
}
