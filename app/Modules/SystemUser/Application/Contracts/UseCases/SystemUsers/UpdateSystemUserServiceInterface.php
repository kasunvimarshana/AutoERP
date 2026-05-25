<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\Contracts\UseCases\SystemUsers;

use Modules\Core\Application\Results\Result;

interface UpdateSystemUserServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}
