<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\User\Constants\PlatformOperatorInvitationStatus;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationTokenCodec;
use Tests\TestCase;

final class PlatformOperatorInvitationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_acceptance_uses_an_explicit_audit_actor_and_completes_registration(): void
    {
        config()->set('module-auth.platform_mfa.enabled', true);
        $fixture = $this->createInvitation();

        $response = $this->postJson('/api/v1/platform/operator-invitations/accept', [
            'token' => $fixture['token'],
            'password' => 'Valid-Platform-Password-2026!',
            'password_confirmation' => 'Valid-Platform-Password-2026!',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', PlatformOperatorStatus::ACTIVE)
            ->assertJsonPath('data.email', 'operator@example.test')
            ->assertJsonStructure(['data' => ['mfa_enrollment' => ['enrollment_proof', 'provisioning_uri']]]);

        $this->assertDatabaseHas('platform_operators', [
            'id' => $fixture['operator_id'],
            'status' => PlatformOperatorStatus::ACTIVE,
        ]);
        $this->assertDatabaseHas('platform_operator_invitations', [
            'id' => $fixture['invitation_id'],
            'status' => PlatformOperatorInvitationStatus::ACCEPTED,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'scope_type' => 'platform',
            'event_name' => 'platform.operator.invitation_accepted',
            'actor_type' => 'user',
            'actor_id' => (string) $fixture['operator_id'],
            'subject_type' => 'platform_operator',
            'subject_id' => (string) $fixture['operator_id'],
        ]);
    }

    public function test_password_policy_violation_returns_field_validation_without_mutating_registration(): void
    {
        $fixture = $this->createInvitation();

        $this->postJson('/api/v1/platform/operator-invitations/accept', [
            'token' => $fixture['token'],
            'password' => 'password@12345',
            'password_confirmation' => 'password@12345',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseHas('platform_operators', [
            'id' => $fixture['operator_id'],
            'status' => PlatformOperatorStatus::INVITED,
        ]);
        $this->assertDatabaseHas('platform_operator_invitations', [
            'id' => $fixture['invitation_id'],
            'status' => PlatformOperatorInvitationStatus::PENDING,
        ]);
        $this->assertDatabaseMissing('auth_platform_operator_password_credentials', [
            'platform_operator_id' => $fixture['operator_id'],
        ]);
    }

    /** @return array{operator_id:int,invitation_id:int,token:string} */
    private function createInvitation(): array
    {
        $now = now();
        $operatorId = (int) DB::table('platform_operators')->insertGetId([
            'row_version' => 1,
            'first_name' => 'Platform',
            'last_name' => 'Administrator',
            'email' => 'operator@example.test',
            'status' => PlatformOperatorStatus::INVITED,
            'credentials_ready_at' => null,
            'invited_at' => $now,
            'activated_at' => null,
            'deactivated_at' => null,
            'created_by_operator_id' => null,
            'updated_by_operator_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $codec = $this->app->make(PlatformOperatorInvitationTokenCodec::class);
        $token = $codec->issue();
        $invitationId = (int) DB::table('platform_operator_invitations')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'platform_operator_id' => $operatorId,
            'created_by_operator_id' => null,
            'token_hash' => $codec->digest($token),
            'delivery_token' => null,
            'status' => PlatformOperatorInvitationStatus::PENDING,
            'expires_at' => $now->copy()->addHour(),
            'accepted_at' => null,
            'revoked_at' => null,
            'revocation_reason' => null,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'operator_id' => $operatorId,
            'invitation_id' => $invitationId,
            'token' => $token,
        ];
    }
}
