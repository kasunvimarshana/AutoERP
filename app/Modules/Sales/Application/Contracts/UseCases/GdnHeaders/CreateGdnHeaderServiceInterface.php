<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\GdnHeaders;

use Modules\Core\Application\Results\Result;

interface CreateGdnHeaderServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}