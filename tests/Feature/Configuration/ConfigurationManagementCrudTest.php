<?php

declare(strict_types=1);

namespace Tests\Feature\Configuration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Configuration\Constants\ConfigurationKey;
use Modules\Configuration\Constants\ConfigurationPermission;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Contracts\MailConfigurationResolverInterface;
use Modules\Configuration\Contracts\SystemPreferencesResolverInterface;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\Support\TenantAuthenticationFixture;
use Tests\Support\TenantUserFixture;
use Tests\TestCase;

final class ConfigurationManagementCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_create_view_update_and_delete_tenant_configuration(): void
    {
        $context = $this->createAuthContext([
            ConfigurationPermission::ENTRIES_VIEW,
            ConfigurationPermission::ENTRIES_MANAGE_TENANT,
        ]);

        // 1. View registered definitions
        $definitionsResponse = $this->withAuth($context)->getJson('/api/v1/configuration/definitions');
        $definitionsResponse->assertOk();
        $definitionsResponse->assertJsonStructure(['data' => [['key', 'label', 'owner', 'value_type']]]);
        $keys = collect($definitionsResponse->json('data'))->pluck('key')->all();
        self::assertContains(ConfigurationKey::MAIL_HOST, $keys);
        self::assertContains(ConfigurationKey::BRANDING_COMPANY_NAME, $keys);
        self::assertContains(ConfigurationKey::LOCALIZATION_DATE_FORMAT, $keys);
        self::assertContains(ConfigurationKey::SYSTEM_SESSION_TIMEOUT_MINUTES, $keys);

        // 2. Initial resolved value falls back to definition default
        $initialResolved = $this->withAuth($context)->getJson('/api/v1/configuration/resolved/'.ConfigurationKey::MAIL_HOST);
        $initialResolved->assertOk();
        $initialResolved->assertJsonPath('data.value', '127.0.0.1');
        $initialResolved->assertJsonPath('data.source_scope', 'default');
        $initialResolved->assertJsonPath('data.uses_default', true);

        // 3. Create tenant-scope override (HTTP POST)
        $createResponse = $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::MAIL_HOST,
            'value' => 'smtp.custom-tenant.com',
        ]);
        $createResponse->assertCreated();
        $createResponse->assertJsonPath('data.key', ConfigurationKey::MAIL_HOST);
        $createResponse->assertJsonPath('data.value', 'smtp.custom-tenant.com');
        $createResponse->assertJsonPath('data.scope', ConfigurationScope::TENANT);
        $createResponse->assertJsonPath('data.row_version', 1);

        // 4. Duplicate creation in same scope fails with 409 Conflict
        $duplicateResponse = $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::MAIL_HOST,
            'value' => 'smtp.another.com',
        ]);
        $duplicateResponse->assertStatus(409);

        // 5. Read exact entry
        $showResponse = $this->withAuth($context)->getJson('/api/v1/configuration/tenant/entries/'.ConfigurationKey::MAIL_HOST);
        $showResponse->assertOk();
        $showResponse->assertJsonPath('data.value', 'smtp.custom-tenant.com');
        $showResponse->assertJsonPath('data.row_version', 1);

        // 6. Resolved endpoint now returns tenant value
        $resolvedResponse = $this->withAuth($context)->getJson('/api/v1/configuration/resolved/'.ConfigurationKey::MAIL_HOST);
        $resolvedResponse->assertOk();
        $resolvedResponse->assertJsonPath('data.value', 'smtp.custom-tenant.com');
        $resolvedResponse->assertJsonPath('data.source_scope', ConfigurationScope::TENANT);
        $resolvedResponse->assertJsonPath('data.uses_default', false);

        // 7. Update with optimistic locking via PUT
        $putResponse = $this->withAuth($context)->putJson('/api/v1/configuration/tenant/entries/'.ConfigurationKey::MAIL_HOST, [
            'expected_version' => 1,
            'value' => 'smtp.updated-put.com',
        ]);
        $putResponse->assertOk();
        $putResponse->assertJsonPath('data.value', 'smtp.updated-put.com');
        $putResponse->assertJsonPath('data.row_version', 2);

        // 8. Update with optimistic locking via PATCH
        $patchResponse = $this->withAuth($context)->patchJson('/api/v1/configuration/tenant/entries/'.ConfigurationKey::MAIL_HOST, [
            'expected_version' => 2,
            'value' => 'smtp.updated-patch.com',
        ]);
        $patchResponse->assertOk();
        $patchResponse->assertJsonPath('data.value', 'smtp.updated-patch.com');
        $patchResponse->assertJsonPath('data.row_version', 3);

        // 9. Stale expected_version fails with 409 Conflict
        $conflictResponse = $this->withAuth($context)->putJson('/api/v1/configuration/tenant/entries/'.ConfigurationKey::MAIL_HOST, [
            'expected_version' => 1,
            'value' => 'smtp.stale.com',
        ]);
        $conflictResponse->assertStatus(409);

        // 10. Delete override with optimistic locking
        $deleteConflict = $this->withAuth($context)->deleteJson('/api/v1/configuration/tenant/entries/'.ConfigurationKey::MAIL_HOST, [
            'expected_version' => 1,
        ]);
        $deleteConflict->assertStatus(409);

        $deleteResponse = $this->withAuth($context)->deleteJson('/api/v1/configuration/tenant/entries/'.ConfigurationKey::MAIL_HOST, [
            'expected_version' => 3,
        ]);
        $deleteResponse->assertNoContent();

        // 11. After deletion, resolve immediately falls back to definition default
        $postDeleteResolved = $this->withAuth($context)->getJson('/api/v1/configuration/resolved/'.ConfigurationKey::MAIL_HOST);
        $postDeleteResolved->assertOk();
        $postDeleteResolved->assertJsonPath('data.value', '127.0.0.1');
        $postDeleteResolved->assertJsonPath('data.source_scope', 'default');
        $postDeleteResolved->assertJsonPath('data.uses_default', true);
    }

    public function test_organization_unit_scope_inheritance_and_automatic_fallback(): void
    {
        $context = $this->createAuthContext([
            ConfigurationPermission::ENTRIES_VIEW,
            ConfigurationPermission::ENTRIES_MANAGE_TENANT,
            ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
        ]);

        $orgUnitId = $this->createBranchUnit($context['tenant_id'], 'North Branch');
        $ouContext = $this->createAuthContext([
            ConfigurationPermission::ENTRIES_VIEW,
            ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
        ], $context['tenant_id'], $orgUnitId);

        // 1. Configure tenant override
        $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::MAIL_FROM_NAME,
            'value' => 'Headquarters Notifications',
        ])->assertCreated();

        // 2. Organization unit resolves to tenant override when no OU override exists
        $orgResolvedBefore = $this->withAuth($ouContext)->getJson(
            '/api/v1/configuration/resolved/'.ConfigurationKey::MAIL_FROM_NAME,
        );
        $orgResolvedBefore->assertOk();
        $orgResolvedBefore->assertJsonPath('data.value', 'Headquarters Notifications');
        $orgResolvedBefore->assertJsonPath('data.source_scope', ConfigurationScope::TENANT);

        // 3. Create organization unit override
        $orgCreate = $this->withAuth($ouContext)->postJson(
            '/api/v1/configuration/organization/entries',
            [
                'key' => ConfigurationKey::MAIL_FROM_NAME,
                'value' => 'North Branch Notifications',
            ],
        );
        $orgCreate->assertCreated();
        $orgCreate->assertJsonPath('data.value', 'North Branch Notifications');
        $orgCreate->assertJsonPath('data.scope', ConfigurationScope::ORGANIZATION_UNIT);
        $orgCreate->assertJsonPath('data.row_version', 1);

        // 4. Organization unit now resolves to its own override
        $orgResolvedAfter = $this->withAuth($ouContext)->getJson(
            '/api/v1/configuration/resolved/'.ConfigurationKey::MAIL_FROM_NAME,
        );
        $orgResolvedAfter->assertOk();
        $orgResolvedAfter->assertJsonPath('data.value', 'North Branch Notifications');
        $orgResolvedAfter->assertJsonPath('data.source_scope', ConfigurationScope::ORGANIZATION_UNIT);

        // 5. Tenant level resolution still resolves to tenant override
        $tenantResolved = $this->withAuth($context)->getJson('/api/v1/configuration/resolved/'.ConfigurationKey::MAIL_FROM_NAME.'?scope=tenant');
        $tenantResolved->assertOk();
        $tenantResolved->assertJsonPath('data.value', 'Headquarters Notifications');
        $tenantResolved->assertJsonPath('data.source_scope', ConfigurationScope::TENANT);

        // 6. Delete organization unit override
        $this->withAuth($ouContext)->deleteJson(
            '/api/v1/configuration/organization/entries/'.ConfigurationKey::MAIL_FROM_NAME,
            ['expected_version' => 1],
        )->assertNoContent();

        // 7. Organization unit immediately falls back to tenant value
        $orgResolvedFallback = $this->withAuth($ouContext)->getJson(
            '/api/v1/configuration/resolved/'.ConfigurationKey::MAIL_FROM_NAME,
        );
        $orgResolvedFallback->assertOk();
        $orgResolvedFallback->assertJsonPath('data.value', 'Headquarters Notifications');
        $orgResolvedFallback->assertJsonPath('data.source_scope', ConfigurationScope::TENANT);
    }

    public function test_resolved_index_endpoint_returns_all_resolved_configurations(): void
    {
        $context = $this->createAuthContext([
            ConfigurationPermission::ENTRIES_VIEW,
            ConfigurationPermission::ENTRIES_MANAGE_TENANT,
        ]);

        $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::BRANDING_COMPANY_NAME,
            'value' => 'Acme Global Corp',
        ])->assertCreated();

        // List all resolved
        $response = $this->withAuth($context)->getJson('/api/v1/configuration/resolved');
        $response->assertOk();
        $data = $response->json('data');
        self::assertIsArray($data);
        self::assertGreaterThanOrEqual(18, count($data));

        $companyEntry = collect($data)->firstWhere('key', ConfigurationKey::BRANDING_COMPANY_NAME);
        self::assertNotNull($companyEntry);
        self::assertSame('Acme Global Corp', $companyEntry['value']);
        self::assertSame(ConfigurationScope::TENANT, $companyEntry['source_scope']);

        // Filter by search
        $searchResponse = $this->withAuth($context)->getJson('/api/v1/configuration/resolved?search=mail');
        $searchResponse->assertOk();
        $searchKeys = collect($searchResponse->json('data'))->pluck('key')->all();
        self::assertContains(ConfigurationKey::MAIL_HOST, $searchKeys);
        self::assertNotContains(ConfigurationKey::BRANDING_COMPANY_NAME, $searchKeys);
    }

    public function test_mail_configuration_resolver_service_resolves_all_email_settings(): void
    {
        $context = $this->createAuthContext([
            ConfigurationPermission::ENTRIES_VIEW,
            ConfigurationPermission::ENTRIES_MANAGE_TENANT,
            ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
        ]);

        $tenantId = $context['tenant_id'];
        $orgUnitId = $this->createBranchUnit($tenantId, 'Finance Unit');
        $ouContext = $this->createAuthContext([
            ConfigurationPermission::ENTRIES_VIEW,
            ConfigurationPermission::ENTRIES_MANAGE_ORGANIZATION,
        ], $tenantId, $orgUnitId);

        // Override settings at tenant level
        $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::MAIL_HOST,
            'value' => 'smtp.tenant-mail.com',
        ])->assertCreated();
        $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::MAIL_PORT,
            'value' => 587,
        ])->assertCreated();
        $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::MAIL_FROM_ADDRESS,
            'value' => 'billing@tenant-mail.com',
        ])->assertCreated();

        // Override from_address at OU level
        $this->withAuth($ouContext)->postJson(
            '/api/v1/configuration/organization/entries',
            [
                'key' => ConfigurationKey::MAIL_FROM_ADDRESS,
                'value' => 'finance-ou@tenant-mail.com',
            ],
        )->assertCreated();

        /** @var MailConfigurationResolverInterface $resolver */
        $resolver = app(MailConfigurationResolverInterface::class);

        // 1. Resolve for tenant
        $tenantMail = $resolver->resolve($tenantId, null);
        self::assertSame('smtp.tenant-mail.com', $tenantMail->host);
        self::assertSame(587, $tenantMail->port);
        self::assertSame('billing@tenant-mail.com', $tenantMail->fromAddress);

        // 2. Resolve for organization unit: inherits host & port from tenant, uses OU fromAddress
        $ouMail = $resolver->resolve($tenantId, $orgUnitId);
        self::assertSame('smtp.tenant-mail.com', $ouMail->host);
        self::assertSame(587, $ouMail->port);
        self::assertSame('finance-ou@tenant-mail.com', $ouMail->fromAddress);

        // 3. Test toMailerConfig
        $mailerConfig = $ouMail->toMailerConfig();
        self::assertSame('smtp.tenant-mail.com', $mailerConfig['host']);
        self::assertSame(587, $mailerConfig['port']);

        // 4. Test configureEnvironment
        $resolver->configureEnvironment($tenantId, $orgUnitId);
        self::assertSame('finance-ou@tenant-mail.com', config('mail.from.address'));
        self::assertSame('smtp.tenant-mail.com', config('mail.mailers.smtp.host'));
    }

    public function test_system_preferences_resolver_service_resolves_all_preferences(): void
    {
        $context = $this->createAuthContext([
            ConfigurationPermission::ENTRIES_VIEW,
            ConfigurationPermission::ENTRIES_MANAGE_TENANT,
        ]);
        $tenantId = $context['tenant_id'];

        $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::BRANDING_COMPANY_NAME,
            'value' => 'Apex Logistics Ltd',
        ])->assertCreated();
        $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::LOCALIZATION_DATE_FORMAT,
            'value' => 'DD/MM/YYYY',
        ])->assertCreated();
        $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::SYSTEM_SESSION_TIMEOUT_MINUTES,
            'value' => 120,
        ])->assertCreated();

        /** @var SystemPreferencesResolverInterface $resolver */
        $resolver = app(SystemPreferencesResolverInterface::class);
        $preferences = $resolver->resolve($tenantId);

        self::assertSame('Apex Logistics Ltd', $preferences->companyName);
        self::assertSame('DD/MM/YYYY', $preferences->dateFormat);
        self::assertSame(120, $preferences->sessionTimeoutMinutes);
        self::assertSame('UTC', $preferences->timezone);
        self::assertSame('USD', $preferences->currency);
    }

    public function test_sensitive_setting_protection_and_secret_permissions(): void
    {
        // 1. Context without sensitive permission
        $context = $this->createAuthContext([
            ConfigurationPermission::ENTRIES_VIEW,
            ConfigurationPermission::ENTRIES_MANAGE_TENANT,
        ]);
        $tenantId = $context['tenant_id'];

        // Attempting to set mail.password fails without ENTRIES_MANAGE_SENSITIVE
        $forbiddenCreate = $this->withAuth($context)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::MAIL_PASSWORD,
            'value' => 'SuperSecretSmtpKey123',
        ]);
        $forbiddenCreate->assertForbidden();

        // 2. Second user context on same tenant with sensitive permission
        $authorizedContext = $this->createAuthContext([
            ConfigurationPermission::ENTRIES_VIEW,
            ConfigurationPermission::ENTRIES_MANAGE_TENANT,
            ConfigurationPermission::ENTRIES_MANAGE_SENSITIVE,
        ], $tenantId, $context['organization_unit_id']);

        // Now succeeds
        $authorizedCreate = $this->withAuth($authorizedContext)->postJson('/api/v1/configuration/tenant/entries', [
            'key' => ConfigurationKey::MAIL_PASSWORD,
            'value' => 'SuperSecretSmtpKey123',
        ]);
        $authorizedCreate->assertCreated();
        // Stored value is protected in response
        $authorizedCreate->assertJsonPath('data.value', null);
        $authorizedCreate->assertJsonPath('data.display_value', 'Configured (protected)');
        $authorizedCreate->assertJsonPath('data.sensitive', true);

        // Resolved endpoint also protects sensitive value
        $resolved = $this->withAuth($authorizedContext)->getJson('/api/v1/configuration/resolved/'.ConfigurationKey::MAIL_PASSWORD);
        $resolved->assertOk();
        $resolved->assertJsonPath('data.value', null);
        $resolved->assertJsonPath('data.display_value', 'Protected value');
        $resolved->assertJsonPath('data.sensitive', true);

        // However, internal domain service decrypts secret for actual mailer transport
        /** @var MailConfigurationResolverInterface $resolver */
        $resolver = app(MailConfigurationResolverInterface::class);
        $mail = $resolver->resolve($tenantId);
        self::assertSame('SuperSecretSmtpKey123', $mail->password);
    }

    private function withAuth(array $context, ?int $organizationUnitId = null): self
    {
        $headers = ['X-Tenant-Id' => (string) $context['tenant_id']];
        if ($organizationUnitId !== null) {
            $headers['X-Organization-Unit-Id'] = (string) $organizationUnitId;
        }

        return $this->withToken($context['token'])->withHeaders($headers);
    }

    private function createBranchUnit(int $tenantId, string $name): int
    {
        $now = now();

        return (int) OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'code' => Str::upper(Str::slug($name)),
            'path' => '/'.Str::slug($name),
            'is_active' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param list<string> $permissions
     * @return array{tenant_id: int, organization_unit_id: int, user_id: int, role_id: int, token: string}
     */
    private function createAuthContext(
        array $permissions = [],
        ?int $existingTenantId = null,
        ?int $existingOrgUnitId = null,
    ): array {
        $now = now();
        if ($existingTenantId !== null) {
            $tenantId = $existingTenantId;
            $organizationUnitId = $existingOrgUnitId ?? (int) OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Main-'.Str::random(4),
                'code' => 'MAIN'.Str::upper(Str::random(3)),
                'path' => '/main-'.Str::lower(Str::random(4)),
                'is_active' => true,
                'row_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $code = strtoupper('CFG'.Str::random(3));
            $tenantId = (int) DB::table('tenants')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'code' => $code,
                'name' => $code.' Tenant',
                'slug' => strtolower($code).'-tenant',
                'status' => 'active',
                'row_version' => 1,
                'status_reason' => 'Integration test tenant.',
                'status_changed_at' => $now,
                'activated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            ActiveTenantSubscriptionFixture::create($tenantId);

            $organizationUnitId = (int) OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Main',
                'code' => 'MAIN',
                'path' => '/main',
                'is_active' => true,
                'row_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $email = 'cfg-user-'.Str::lower(Str::random(8)).'@example.test';
        $userId = (int) TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Config',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => 'secret-password',
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('user_organization_units')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'status' => 'active',
            'is_default' => true,
            'default_marker' => 'default',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Config Test Role '.Str::random(6),
            'guard_name' => 'auth-api',
            'description' => 'Config test role',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($permissions as $permissionName) {
            $permissionId = DB::table('permissions')
                ->where('tenant_id', $tenantId)
                ->where('name', $permissionName)
                ->where('guard_name', 'auth-api')
                ->value('id');

            if ($permissionId === null) {
                $permissionId = (int) DB::table('permissions')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => $permissionName,
                    'guard_name' => 'auth-api',
                    'module' => 'configuration',
                    'description' => $permissionName,
                    'is_active' => true,
                    'row_version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $alreadyLinked = DB::table('role_permissions')
                ->where('tenant_id', $tenantId)
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $alreadyLinked) {
                DB::table('role_permissions')->insert([
                    'tenant_id' => $tenantId,
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'row_version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        TenantAuthenticationFixture::provision($tenantId, $userId, $email);

        $token = (string) $this->withHeader('X-Tenant-Id', (string) $tenantId)->postJson('/api/v1/auth/login', [
            'organization_unit_id' => $organizationUnitId,
            'identifier' => $email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'token' => $token,
        ];
    }

    private function grantPermissionToAuthContext(array $context, string $permissionName): void
    {
        $now = now();
        $permissionId = (int) DB::table('permissions')->insertGetId([
            'tenant_id' => $context['tenant_id'],
            'name' => $permissionName,
            'guard_name' => 'auth-api',
            'module' => 'configuration',
            'description' => $permissionName,
            'is_active' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('role_permissions')->insert([
            'tenant_id' => $context['tenant_id'],
            'role_id' => $context['role_id'],
            'permission_id' => $permissionId,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
