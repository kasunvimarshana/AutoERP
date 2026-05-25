<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Countries;

use Modules\Core\Application\Results\Result;

interface ListCountriesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
