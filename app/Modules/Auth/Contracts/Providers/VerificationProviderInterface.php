<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts\Providers;

use Modules\Auth\DTOs\VerificationChallengeRequestData;
use Modules\Auth\DTOs\VerificationChallengeVerifyData;

interface VerificationProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function requestChallenge(VerificationChallengeRequestData $data): array;

    public function verifyChallenge(VerificationChallengeVerifyData $data): bool;
}
