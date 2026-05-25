<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Timezones;

use Modules\Core\Application\Results\Result;

interface CreateTimezoneServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
