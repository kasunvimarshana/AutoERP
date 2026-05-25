<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\Contracts\UseCases\SystemUsers;

use Modules\Core\Application\Results\Result;

interface ListSystemUsersServiceInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function execute(array $filters): Result;
}
