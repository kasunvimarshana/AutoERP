<?php

declare(strict_types=1);

namespace Modules\Auth\Services\Mfa;

final class PlatformMfaPolicy
{
    public function isEnabled(): bool
    {
        return (bool) config('module-auth.platform_mfa.enabled', false);
    }

    public function isEnrollmentRequired(): bool
    {
        return $this->isEnabled()
            && (bool) config('module-auth.platform_mfa.required', false);
    }

    public function shouldChallengeLogin(bool $hasActiveMethod): bool
    {
        return $this->isEnabled()
            && $hasActiveMethod
            && (bool) config('module-auth.platform_mfa.login_challenge', true);
    }

    public function isMfaStepUpRequired(): bool
    {
        return $this->isEnabled()
            && (bool) config('module-auth.platform_mfa.step_up_required', false);
    }
}
