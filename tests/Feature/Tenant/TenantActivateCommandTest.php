<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Constants\RegistrationInvitationPurpose;
use Modules\Auth\Constants\RegistrationInvitationStatus;
use Modules\Core\Contracts\TenantAccessProvisionerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Constants\TenantOnboardingStatus;
use Modules\Tenant\Constants\TenantStatus;
use Modules\User\Constants\UserOrganizationUnitStatus;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantAuthenticationFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class TenantActivateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_activate_command_uses_system_audit_actor_without_request_user(): void
    {
        config()->set('tenant.resolution.local_fallback_enabled', true);
        config()->set('tenant.resolution.local_fallback_tenant_code', 'CLI-ACTIVATE');

        $tenantId = $this->createDraftTenant();
        ActiveTenantSubscriptionFixture::create($tenantId);

        $organizationUnitId = OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'CLI activation root',
            'code' => 'CLIROOT',
        ]);

        $executionContext = app(TenantExecutionContextInterface::class);
        $roleId = $executionContext->runForTenant(
            $tenantId,
            static fn (): int => (int) app(TenantAccessProvisionerInterface::class)
                ->provision($tenantId)['role_id'],
        );

        $email = 'cli-activation-admin@example.test';
        $userId = TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'CLI',
            'last_name' => 'Administrator',
            'email' => $email,
            'status' => 'active',
        ]);
        TenantAuthenticationFixture::provision($tenantId, $userId, $email);
        $this->assignAdministratorAccess($tenantId, $userId, $organizationUnitId, $roleId);
        $invitationId = $this->acceptedInvitation($tenantId, $userId, $organizationUnitId, $roleId, $email);
        $this->readyOnboardingState($tenantId, $organizationUnitId, $roleId, $invitationId, $email);

        $this->artisan('tenant:activate', [
            'tenant' => (string) $tenantId,
            'expected-version' => 1,
            '--reason' => 'CLI activation regression test.',
        ])
            ->expectsOutput('Tenant activated.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
            'status' => TenantStatus::ACTIVE,
            'row_version' => 2,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'scope_type' => 'platform',
            'tenant_id' => $tenantId,
            'event_name' => 'tenant.status_changed',
            'actor_type' => 'system',
            'actor_id' => 'tenant-lifecycle-command',
        ]);
    }

    private function createDraftTenant(): int
    {
        $now = now();
        $currencyId = (int) DB::table('currencies')->insertGetId([
            'row_version' => 1,
            'code' => 'TST',
            'name' => 'Test currency',
            'symbol' => 'T',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) DB::table('tenants')->insertGetId([
            'row_version' => 1,
            'uuid' => (string) Str::uuid(),
            'code' => 'CLI-ACTIVATE',
            'name' => 'CLI activation tenant',
            'slug' => 'cli-activate',
            'logo_object_key' => null,
            'logo_mime_type' => null,
            'logo_size_bytes' => null,
            'base_currency_id' => $currencyId,
            'status' => TenantStatus::DRAFT,
            'status_reason' => 'Command regression fixture.',
            'status_changed_at' => $now,
            'activated_at' => null,
            'suspended_at' => null,
            'archived_at' => null,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function assignAdministratorAccess(
        int $tenantId,
        int $userId,
        int $organizationUnitId,
        int $roleId,
    ): void {
        $now = now();
        DB::table('user_roles')->insert([
            'row_version' => 1,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('user_organization_units')->insert([
            'row_version' => 1,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'status' => UserOrganizationUnitStatus::ACTIVE,
            'is_default' => true,
            'default_marker' => UserOrganizationUnitStatus::DEFAULT_MARKER,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function acceptedInvitation(
        int $tenantId,
        int $userId,
        int $organizationUnitId,
        int $roleId,
        string $email,
    ): int {
        $now = now();

        return (int) DB::table('auth_registration_invitations')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'row_version' => 2,
            'tenant_id' => $tenantId,
            'user_id' => null,
            'organization_unit_id' => $organizationUnitId,
            'role_id' => $roleId,
            'email' => $email,
            'token_hash' => hash('sha256', 'cli-activation-regression'),
            'delivery_token' => null,
            'purpose' => RegistrationInvitationPurpose::INITIAL_ADMINISTRATOR,
            'status' => RegistrationInvitationStatus::ACCEPTED,
            'expires_at' => $now->copy()->addHour(),
            'accepted_at' => $now,
            'accepted_by_user_id' => $userId,
            'revoked_at' => null,
            'revocation_reason' => null,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function readyOnboardingState(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        int $invitationId,
        string $email,
    ): void {
        $now = now();
        DB::table('tenant_onboarding_states')->insert([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'status' => TenantOnboardingStatus::READY,
            'operation_id' => null,
            'operation_started_at' => null,
            'operation_lease_expires_at' => null,
            'initial_admin_email' => $email,
            'root_organization_unit_id' => $organizationUnitId,
            'super_admin_role_id' => $roleId,
            'invitation_id' => $invitationId,
            'failed_step' => null,
            'last_error_code' => null,
            'last_error_message' => null,
            'correlation_id' => null,
            'provisioned_at' => $now,
            'completed_at' => null,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
