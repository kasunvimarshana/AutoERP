<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Holidays;

use Modules\Core\Application\Results\Result;

interface UpdateHolidayServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}