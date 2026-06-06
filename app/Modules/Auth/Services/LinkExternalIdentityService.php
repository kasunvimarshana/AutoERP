<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\LinkExternalIdentityData;
use Modules\Core\Results\Result;

final class LinkExternalIdentityService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function linkExternalIdentity(LinkExternalIdentityData $data): Result
    {
        return $this->workflow->linkExternalIdentity($data);
    }
}
