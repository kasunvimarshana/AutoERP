<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\RegistrationData;
use Modules\Core\Application\Results\Result;

interface RegisterServiceInterface
{
    public function register(RegistrationData $data): Result;
}
