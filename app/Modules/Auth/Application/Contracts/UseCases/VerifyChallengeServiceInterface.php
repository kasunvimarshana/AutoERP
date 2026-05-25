<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\UseCases;

use Modules\Auth\Application\DTOs\VerificationChallengeVerifyData;
use Modules\Core\Application\Results\Result;

interface VerifyChallengeServiceInterface
{
    public function verifyChallenge(VerificationChallengeVerifyData $data): Result;
}
