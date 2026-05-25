<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\LogoutData;
use Modules\Core\Application\Results\Result;

interface LogoutServiceInterface
{
    public function logout(LogoutData $data): Result;
}
