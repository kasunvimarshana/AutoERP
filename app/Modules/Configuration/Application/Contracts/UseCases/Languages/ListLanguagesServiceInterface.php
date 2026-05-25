<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Languages;

use Modules\Core\Application\Results\Result;

interface ListLanguagesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
