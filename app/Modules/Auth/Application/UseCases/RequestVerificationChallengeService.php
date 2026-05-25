<?php

declare(strict_types=1);

namespace Modules\Auth\Application\UseCases;

use Modules\Auth\Application\Contracts\UseCases\RequestVerificationChallengeServiceInterface;
use Modules\Auth\Application\DTOs\VerificationChallengeRequestData;
use Modules\Core\Application\Results\Result;

final class RequestVerificationChallengeService implements RequestVerificationChallengeServiceInterface
{
    public function __construct(private readonly AuthWorkflowService $workflow)
    {
    }

    public function requestVerificationChallenge(VerificationChallengeRequestData $data): Result
    {
        return $this->workflow->requestVerificationChallenge($data);
    }
}
