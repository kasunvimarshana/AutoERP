<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\UnlinkExternalIdentityData;
use Modules\Core\Results\Result;

final class UnlinkExternalIdentityService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function unlinkExternalIdentity(UnlinkExternalIdentityData $data): Result
    {
        return $this->workflow->unlinkExternalIdentity($data);
    }
}
