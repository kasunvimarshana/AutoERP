<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\DTOs\TokenIssueData;
use Modules\Core\Application\Results\Result;

final class IssueTokenService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function issueToken(TokenIssueData $data): Result
    {
        return $this->workflow->issueToken($data);
    }
}
