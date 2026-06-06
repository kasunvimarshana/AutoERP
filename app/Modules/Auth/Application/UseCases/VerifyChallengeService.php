<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\DTOs\VerificationChallengeVerifyData;
use Modules\Core\Application\Results\Result;

final class VerifyChallengeService
{
    public function __construct(private readonly AuthWorkflowService $workflow) {}

    public function verifyChallenge(VerificationChallengeVerifyData $data): Result
    {
        return $this->workflow->verifyChallenge($data);
    }
}
