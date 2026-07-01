<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Constants\RegistrationInvitationPurpose;
use Modules\Auth\Constants\RegistrationInvitationStatus;
use Modules\Auth\Services\Security\OpaqueTokenCodec;
use Modules\Tenant\Constants\TenantStatus;
use Tests\TestCase;

final class InitialAdministratorInvitationInspectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_initial_administrator_invitation_can_be_inspected_for_draft_tenant(): void
    {
        $now = now();
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'row_version' => 1,
            'uuid' => (string) Str::uuid(),
            'code' => 'INVITE-DRAFT',
            'name' => 'Draft invitation tenant',
            'slug' => 'invite-draft',
            'logo_object_key' => null,
            'logo_mime_type' => null,
            'logo_size_bytes' => null,
            'base_currency_id' => null,
            'status' => TenantStatus::DRAFT,
            'status_reason' => 'Initial administrator registration test.',
            'status_changed_at' => $now,
            'activated_at' => null,
            'suspended_at' => null,
            'archived_at' => null,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = bin2hex(random_bytes(32));
        DB::table('auth_registration_invitations')->insert([
            'public_id' => (string) Str::uuid(),
            'row_version' => 1,
            'tenant_id' => $tenantId,
            'user_id' => null,
            'organization_unit_id' => null,
            'role_id' => null,
            'email' => 'tenantadmin@example.test',
            'token_hash' => $this->app->make(OpaqueTokenCodec::class)
                ->digestArbitrary($token, 'registration-invitation'),
            'delivery_token' => null,
            'purpose' => RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR,
            'status' => RegistrationInvitationStatus::PENDING,
            'expires_at' => $now->copy()->addHour(),
            'accepted_at' => null,
            'accepted_by_user_id' => null,
            'revoked_at' => null,
            'revocation_reason' => null,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->postJson('/api/v1/auth/initial-administrator/inspect', [
            'token' => $token,
        ])
            ->assertOk()
            ->assertJsonPath('data.tenant_name', 'Draft invitation tenant');
    }
}
