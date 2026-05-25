<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Countries;

use Modules\Core\Application\Results\Result;

interface GetCountryServiceInterface
{
    public function execute(int|string $id): Result;
}
