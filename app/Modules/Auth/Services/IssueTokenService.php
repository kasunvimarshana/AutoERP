<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\TokenIssueData;
use Modules\Core\Results\Result;

final class IssueTokenService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function issueToken(TokenIssueData $data): Result
    {
        return $this->workflow->issueToken($data);
    }
}
