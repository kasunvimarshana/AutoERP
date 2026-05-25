<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\Providers;

use Modules\Auth\Application\DTOs\VerificationChallengeRequestData;
use Modules\Auth\Application\DTOs\VerificationChallengeVerifyData;

interface VerificationProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function requestChallenge(VerificationChallengeRequestData $data): array;

    public function verifyChallenge(VerificationChallengeVerifyData $data): bool;
}
