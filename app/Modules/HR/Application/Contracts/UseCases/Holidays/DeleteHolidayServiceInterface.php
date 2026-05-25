<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Holidays;

use Modules\Core\Application\Results\Result;

interface DeleteHolidayServiceInterface
{
    public function execute(int|string $id): Result;
}