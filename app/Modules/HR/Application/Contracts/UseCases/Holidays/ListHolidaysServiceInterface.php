<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Holidays;

use Modules\Core\Application\Results\Result;

interface ListHolidaysServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}