<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Timezones;

use Modules\Core\Application\Results\Result;

interface DeleteTimezoneServiceInterface
{
    public function execute(int|string $id): Result;
}
