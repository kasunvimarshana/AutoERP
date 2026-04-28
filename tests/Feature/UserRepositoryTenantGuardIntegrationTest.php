<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Tests\TestCase;

class UserRepositoryTenantGuardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenants();
        $this->seedUsers();
    }

    public function test_change_password_updates_only_within_tenant_context(): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        $this->bindTenantHeaderRequest(11);
        $repository->changePassword(1101, Hash::make('new-password-11'));

        $tenant11Password = (string) DB::table('users')->where('id', 1101)->value('password');
        $tenant12Password = (string) DB::table('users')->where('id', 1201)->value('password');

        $this->assertTrue(Hash::check('new-password-11', $tenant11Password));
        $this->assertTrue(Hash::check('original-password-12', $tenant12Password));

        $repository->changePassword(1201, Hash::make('blocked-cross-tenant'));

        $tenant12PasswordAfterBlockedAttempt = (string) DB::table('users')->where('id', 1201)->value('password');
        $this->assertTrue(Hash::check('original-password-12', $tenant12PasswordAfterBlockedAttempt));
    }

    public function test_update_avatar_updates_only_within_tenant_context(): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        $this->bindTenantHeaderRequest(11);

        $repository->updateAvatar(1101, 'avatars/1101/new.png');
        $repository->updateAvatar(1201, 'avatars/1201/blocked.png');

        $tenant11Avatar = DB::table('users')->where('id', 1101)->value('avatar');
        $tenant12Avatar = DB::table('users')->where('id', 1201)->value('avatar');

        $this->assertSame('avatars/1101/new.png', $tenant11Avatar);
        $this->assertSame('avatars/1201/original.png', $tenant12Avatar);
    }

    public function test_verify_password_is_tenant_scoped(): void
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);

        $this->bindTenantHeaderRequest(11);

        $this->assertTrue($repository->verifyPassword(1101, 'original-password-11'));
        $this->assertFalse($repository->verifyPassword(1201, 'original-password-12'));
    }

    private function bindTenantHeaderRequest(int $tenantId): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_TENANT_ID' => (string) $tenantId,
        ]);

        $request->setUserResolver(static fn () => null);
        $this->app->instance('request', $request);
    }

    private function seedTenants(): void
    {
        foreach ([11, 12] as $tenantId) {
            DB::table('tenants')->insert([
                'id' => $tenantId,
                'name' => 'Tenant '.$tenantId,
                'slug' => 'tenant-'.$tenantId,
                'domain' => null,
                'logo_path' => null,
                'database_config' => null,
                'mail_config' => null,
                'cache_config' => null,
                'queue_config' => null,
                'feature_flags' => null,
                'api_keys' => null,
                'settings' => null,
                'plan' => 'free',
                'tenant_plan_id' => null,
                'status' => 'active',
                'active' => true,
                'trial_ends_at' => null,
                'subscription_ends_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }
    }

    private function seedUsers(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1101,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'first_name' => 'Tenant',
                'last_name' => 'Eleven',
                'email' => 'tenant11.user@example.com',
                'email_verified_at' => null,
                'password' => Hash::make('original-password-11'),
                'phone' => null,
                'avatar' => 'avatars/1101/original.png',
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 1201,
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'first_name' => 'Tenant',
                'last_name' => 'Twelve',
                'email' => 'tenant12.user@example.com',
                'email_verified_at' => null,
                'password' => Hash::make('original-password-12'),
                'phone' => null,
                'avatar' => 'avatars/1201/original.png',
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }
}
