<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\TaxGroups;

use Modules\Core\Application\Results\Result;

interface GetTaxGroupServiceInterface
{
    public function execute(int|string $id): Result;
}
