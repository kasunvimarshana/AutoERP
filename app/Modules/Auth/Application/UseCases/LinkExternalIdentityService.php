<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\UseCases\LinkExternalIdentityServiceInterface;
use Modules\Auth\Application\DTOs\LinkExternalIdentityData;
use Modules\Core\Application\Results\Result;

final class LinkExternalIdentityService implements LinkExternalIdentityServiceInterface
{
    public function __construct(private readonly AuthWorkflowService $workflow)
    {
    }

    public function linkExternalIdentity(LinkExternalIdentityData $data): Result
    {
        return $this->workflow->linkExternalIdentity($data);
    }
}
