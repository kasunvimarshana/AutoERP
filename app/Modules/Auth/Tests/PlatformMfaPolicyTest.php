<?php

declare(strict_types=1);

namespace Modules\Auth\Tests;

use Modules\Auth\Services\Mfa\PlatformMfaPolicy;
use Tests\TestCase;

final class PlatformMfaPolicyTest extends TestCase
{
    public function test_disabling_mfa_disables_enrollment_login_challenge_and_mfa_step_up(): void
    {
        config()->set('module-auth.platform_mfa', [
            'enabled' => false,
            'required' => true,
            'login_challenge' => true,
            'step_up_required' => true,
        ]);

        $policy = new PlatformMfaPolicy();

        self::assertFalse($policy->isEnabled());
        self::assertFalse($policy->isEnrollmentRequired());
        self::assertFalse($policy->shouldChallengeLogin(true));
        self::assertFalse($policy->isMfaStepUpRequired());
    }

    public function test_enabled_optional_mfa_can_challenge_only_enrolled_operators(): void
    {
        config()->set('module-auth.platform_mfa', [
            'enabled' => true,
            'required' => false,
            'login_challenge' => true,
            'step_up_required' => false,
        ]);

        $policy = new PlatformMfaPolicy();

        self::assertFalse($policy->isEnrollmentRequired());
        self::assertFalse($policy->shouldChallengeLogin(false));
        self::assertTrue($policy->shouldChallengeLogin(true));
        self::assertFalse($policy->isMfaStepUpRequired());
    }

    public function test_login_challenge_and_step_up_are_independent_policies(): void
    {
        config()->set('module-auth.platform_mfa', [
            'enabled' => true,
            'required' => true,
            'login_challenge' => false,
            'step_up_required' => true,
        ]);

        $policy = new PlatformMfaPolicy();

        self::assertTrue($policy->isEnrollmentRequired());
        self::assertFalse($policy->shouldChallengeLogin(true));
        self::assertTrue($policy->isMfaStepUpRequired());
    }
}
