<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts\UseCases\Countries;

use Modules\Core\Application\Results\Result;

interface CreateCountryServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}
