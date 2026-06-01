<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\GdnLines;

use Modules\Core\Application\Results\Result;

interface ListGdnLinesServiceInterface
{
    /**
     * @param  array<string, mixed>  $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
