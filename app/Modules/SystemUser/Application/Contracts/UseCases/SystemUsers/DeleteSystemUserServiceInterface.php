<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\Contracts\UseCases\SystemUsers;

use Modules\Core\Application\Results\Result;

interface DeleteSystemUserServiceInterface
{
    public function execute(int|string $id): Result;
}
