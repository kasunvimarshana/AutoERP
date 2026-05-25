<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\VerificationChallengeRequestData;
use Modules\Core\Application\Results\Result;

interface RequestVerificationChallengeServiceInterface
{
    public function requestVerificationChallenge(VerificationChallengeRequestData $data): Result;
}
