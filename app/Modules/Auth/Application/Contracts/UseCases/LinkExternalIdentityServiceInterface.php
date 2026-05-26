<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\LinkExternalIdentityData;
use Modules\Core\Application\Results\Result;

interface LinkExternalIdentityServiceInterface
{
    public function linkExternalIdentity(LinkExternalIdentityData $data): Result;
}
