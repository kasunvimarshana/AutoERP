<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\TaxGroups;

use Modules\Core\Application\Results\Result;

interface CreateTaxGroupServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
