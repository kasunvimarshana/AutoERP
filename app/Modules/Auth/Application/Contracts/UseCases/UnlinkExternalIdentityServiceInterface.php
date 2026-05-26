<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\UnlinkExternalIdentityData;
use Modules\Core\Application\Results\Result;

interface UnlinkExternalIdentityServiceInterface
{
    public function unlinkExternalIdentity(UnlinkExternalIdentityData $data): Result;
}
