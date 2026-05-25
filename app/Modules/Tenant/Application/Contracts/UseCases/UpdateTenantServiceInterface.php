<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts\UseCases;

use Modules\Core\Application\Results\Result;

interface UpdateTenantServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
