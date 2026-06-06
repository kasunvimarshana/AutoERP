<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\VerificationChallengeRequestData;
use Modules\Core\Results\Result;

final class RequestVerificationChallengeService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function requestVerificationChallenge(VerificationChallengeRequestData $data): Result
    {
        return $this->workflow->requestVerificationChallenge($data);
    }
}
