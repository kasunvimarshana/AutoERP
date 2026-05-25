<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\AuthorizeClientData;
use Modules\Core\Application\Results\Result;

interface AuthorizeClientServiceInterface
{
    public function authorizeClient(AuthorizeClientData $data): Result;
}
