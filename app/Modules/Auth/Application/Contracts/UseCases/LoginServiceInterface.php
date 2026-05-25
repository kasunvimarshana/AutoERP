<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\LoginData;
use Modules\Core\Application\Results\Result;

interface LoginServiceInterface
{
    public function login(LoginData $data): Result;
}
