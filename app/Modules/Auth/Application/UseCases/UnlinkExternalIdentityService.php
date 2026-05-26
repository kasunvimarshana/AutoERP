<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\UseCases\UnlinkExternalIdentityServiceInterface;
use Modules\Auth\Application\DTOs\UnlinkExternalIdentityData;
use Modules\Core\Application\Results\Result;

final class UnlinkExternalIdentityService implements UnlinkExternalIdentityServiceInterface
{
    public function __construct(private readonly AuthWorkflowService $workflow)
    {
    }

    public function unlinkExternalIdentity(UnlinkExternalIdentityData $data): Result
    {
        return $this->workflow->unlinkExternalIdentity($data);
    }
}
