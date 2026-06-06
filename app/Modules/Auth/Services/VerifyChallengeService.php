<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\DTOs\VerificationChallengeVerifyData;
use Modules\Core\Results\Result;

final class VerifyChallengeService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function verifyChallenge(VerificationChallengeVerifyData $data): Result
    {
        return $this->workflow->verifyChallenge($data);
    }
}
