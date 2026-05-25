<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Timezones;

use Modules\Core\Application\Results\Result;

interface ListTimezonesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
