<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Contracts\CreatePermissionServiceInterface;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\Contracts\DeletePermissionServiceInterface;
use Modules\User\Application\Contracts\DeleteRoleServiceInterface;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Application\Contracts\FindPermissionServiceInterface;
use Modules\User\Application\Contracts\FindRoleServiceInterface;
use Modules\User\Application\Contracts\FindUserAttachmentsServiceInterface;
use Modules\User\Application\Contracts\FindUserDevicesServiceInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\User\Application\Contracts\UpdatePreferencesServiceInterface;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\Contracts\UpsertUserDeviceServiceInterface;
use Modules\User\Application\Contracts\DeleteUserDeviceServiceInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\UserPreferences;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class UserEndpointsAuthenticatedTest extends TestCase
{
    private static bool $passportKeysPrepared = false;

    /** @var FindUserServiceInterface&MockObject */
    private FindUserServiceInterface $findUserService;

    /** @var CreateUserServiceInterface&MockObject */
    private CreateUserServiceInterface $createUserService;

    /** @var UpdateUserServiceInterface&MockObject */
    private UpdateUserServiceInterface $updateUserService;

    /** @var DeleteUserServiceInterface&MockObject */
    private DeleteUserServiceInterface $deleteUserService;

    /** @var AssignRoleServiceInterface&MockObject */
    private AssignRoleServiceInterface $assignRoleService;

    /** @var UpdatePreferencesServiceInterface&MockObject */
    private UpdatePreferencesServiceInterface $updatePreferencesService;

    /** @var FindUserAttachmentsServiceInterface&MockObject */
    private FindUserAttachmentsServiceInterface $findUserAttachmentsService;

    /** @var FindUserDevicesServiceInterface&MockObject */
    private FindUserDevicesServiceInterface $findUserDevicesService;

    /** @var UploadAvatarServiceInterface&MockObject */
    private UploadAvatarServiceInterface $uploadAvatarService;

    /** @var UpdateProfileServiceInterface&MockObject */
    private UpdateProfileServiceInterface $updateProfileService;

    /** @var ChangePasswordServiceInterface&MockObject */
    private ChangePasswordServiceInterface $changePasswordService;

    /** @var UpsertUserDeviceServiceInterface&MockObject */
    private UpsertUserDeviceServiceInterface $upsertUserDeviceService;

    /** @var DeleteUserDeviceServiceInterface&MockObject */
    private DeleteUserDeviceServiceInterface $deleteUserDeviceService;

    /** @var FindRoleServiceInterface&MockObject */
    private FindRoleServiceInterface $findRoleService;

    /** @var CreateRoleServiceInterface&MockObject */
    private CreateRoleServiceInterface $createRoleService;

    /** @var DeleteRoleServiceInterface&MockObject */
    private DeleteRoleServiceInterface $deleteRoleService;

    /** @var SyncRolePermissionsServiceInterface&MockObject */
    private SyncRolePermissionsServiceInterface $syncRolePermissionsService;

    /** @var FindPermissionServiceInterface&MockObject */
    private FindPermissionServiceInterface $findPermissionService;

    /** @var CreatePermissionServiceInterface&MockObject */
    private CreatePermissionServiceInterface $createPermissionService;

    /** @var DeletePermissionServiceInterface&MockObject */
    private DeletePermissionServiceInterface $deletePermissionService;

    /** @var AuthorizationServiceInterface&MockObject */
    private AuthorizationServiceInterface $authorizationService;

    private UserModel $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preparePassportKeys();

        // Wire all services as mocks
        $this->findUserService = $this->createMock(FindUserServiceInterface::class);
        $this->app->instance(FindUserServiceInterface::class, $this->findUserService);

        $this->createUserService = $this->createMock(CreateUserServiceInterface::class);
        $this->app->instance(CreateUserServiceInterface::class, $this->createUserService);

        $this->updateUserService = $this->createMock(UpdateUserServiceInterface::class);
        $this->app->instance(UpdateUserServiceInterface::class, $this->updateUserService);

        $this->deleteUserService = $this->createMock(DeleteUserServiceInterface::class);
        $this->app->instance(DeleteUserServiceInterface::class, $this->deleteUserService);

        $this->assignRoleService = $this->createMock(AssignRoleServiceInterface::class);
        $this->app->instance(AssignRoleServiceInterface::class, $this->assignRoleService);

        $this->updatePreferencesService = $this->createMock(UpdatePreferencesServiceInterface::class);
        $this->app->instance(UpdatePreferencesServiceInterface::class, $this->updatePreferencesService);

        $this->findUserAttachmentsService = $this->createMock(FindUserAttachmentsServiceInterface::class);
        $this->app->instance(FindUserAttachmentsServiceInterface::class, $this->findUserAttachmentsService);

        $this->findUserDevicesService = $this->createMock(FindUserDevicesServiceInterface::class);
        $this->app->instance(FindUserDevicesServiceInterface::class, $this->findUserDevicesService);

        $this->uploadAvatarService = $this->createMock(UploadAvatarServiceInterface::class);
        $this->app->instance(UploadAvatarServiceInterface::class, $this->uploadAvatarService);

        $this->updateProfileService = $this->createMock(UpdateProfileServiceInterface::class);
        $this->app->instance(UpdateProfileServiceInterface::class, $this->updateProfileService);

        $this->changePasswordService = $this->createMock(ChangePasswordServiceInterface::class);
        $this->app->instance(ChangePasswordServiceInterface::class, $this->changePasswordService);

        $this->upsertUserDeviceService = $this->createMock(UpsertUserDeviceServiceInterface::class);
        $this->app->instance(UpsertUserDeviceServiceInterface::class, $this->upsertUserDeviceService);

        $this->deleteUserDeviceService = $this->createMock(DeleteUserDeviceServiceInterface::class);
        $this->app->instance(DeleteUserDeviceServiceInterface::class, $this->deleteUserDeviceService);

        $this->findRoleService = $this->createMock(FindRoleServiceInterface::class);
        $this->app->instance(FindRoleServiceInterface::class, $this->findRoleService);

        $this->createRoleService = $this->createMock(CreateRoleServiceInterface::class);
        $this->app->instance(CreateRoleServiceInterface::class, $this->createRoleService);

        $this->deleteRoleService = $this->createMock(DeleteRoleServiceInterface::class);
        $this->app->instance(DeleteRoleServiceInterface::class, $this->deleteRoleService);

        $this->syncRolePermissionsService = $this->createMock(SyncRolePermissionsServiceInterface::class);
        $this->app->instance(SyncRolePermissionsServiceInterface::class, $this->syncRolePermissionsService);

        $this->findPermissionService = $this->createMock(FindPermissionServiceInterface::class);
        $this->app->instance(FindPermissionServiceInterface::class, $this->findPermissionService);

        $this->createPermissionService = $this->createMock(CreatePermissionServiceInterface::class);
        $this->app->instance(CreatePermissionServiceInterface::class, $this->createPermissionService);

        $this->deletePermissionService = $this->createMock(DeletePermissionServiceInterface::class);
        $this->app->instance(DeletePermissionServiceInterface::class, $this->deletePermissionService);

        // Authorization service mock — always allow (bypasses Gate/Policy DB queries)
        $this->authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $this->authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $this->authorizationService);

        // Tenant config mocks
        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);
        $this->app->instance(TenantConfigManagerInterface::class, $this->createMock(TenantConfigManagerInterface::class));

        // Suppress HIBP network calls from Password::uncompromised() rules
        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response('', 404),
        ]);

        // Presence verifier: unique:users,email → 0, everything else → 1 (exists)
        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                // unique constraints — 0 means the value is available (not taken)
                if (in_array($column, ['email', 'name'], true)) {
                    return 0;
                }

                // exists constraints — 1 means the record exists
                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        // Authenticated user model
        $this->authUser = new UserModel([
            'tenant_id' => 1,
            'email' => 'user.test@example.com',
            'password' => bcrypt('Password1!'),
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildUserEntity(int $id = 10): User
    {
        return new User(
            tenantId: 1,
            orgUnitId: null,
            email: new Email('john.doe@example.com'),
            emailVerifiedAt: null,
            password: 'hashed',
            rememberToken: null,
            status: 'active',
            firstName: 'John',
            lastName: 'Doe',
            preferences: new UserPreferences,
            active: true,
            id: $id,
        );
    }

    private function buildRoleEntity(int $id = 5): Role
    {
        return new Role(tenantId: 1, name: 'manager', guardName: 'api', id: $id);
    }

    private function buildPermissionEntity(int $id = 3): Permission
    {
        return new Permission(tenantId: 1, name: 'users.view', guardName: 'api', module: 'User', id: $id);
    }

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')->actingAs(
            $this->authUser,
            (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'))
        );
    }

    // -------------------------------------------------------------------------
    // GET /api/users
    // -------------------------------------------------------------------------

    public function test_index_returns_paginated_users(): void
    {
        $user = $this->buildUserEntity();
        $paginator = new LengthAwarePaginator(
            collect([$user]),
            1,
            15,
            1,
        );

        $this->findUserService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->actingAsUser()->getJson('/api/users');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.email', 'john.doe@example.com')
            ->assertJsonPath('data.0.first_name', 'John');
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/users')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // GET /api/users/{user}
    // -------------------------------------------------------------------------

    public function test_show_returns_user(): void
    {
        $user = $this->buildUserEntity(10);

        $this->findUserService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($user);

        $response = $this->actingAsUser()->getJson('/api/users/10');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 10)
            ->assertJsonPath('data.email', 'john.doe@example.com');
    }

    public function test_show_returns_404_when_user_not_found(): void
    {
        $this->findUserService
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->actingAsUser()->getJson('/api/users/999');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // -------------------------------------------------------------------------
    // POST /api/users
    // -------------------------------------------------------------------------

    public function test_store_creates_user_and_returns_201(): void
    {
        $user = $this->buildUserEntity(11);

        $this->createUserService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($user);

        $response = $this->actingAsUser()->postJson('/api/users', [
            'tenant_id' => 1,
            'email' => 'new.user@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.first_name', 'John');
    }

    public function test_store_returns_422_on_missing_required_fields(): void
    {
        $response = $this->actingAsUser()->postJson('/api/users', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    // -------------------------------------------------------------------------
    // PUT /api/users/{user}
    // -------------------------------------------------------------------------

    public function test_update_returns_200_with_updated_user(): void
    {
        $user = $this->buildUserEntity(10);

        $this->findUserService->method('find')->willReturn($user);
        $this->updateUserService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($user);

        $response = $this->actingAsUser()->putJson('/api/users/10', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 10);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/users/{user}
    // -------------------------------------------------------------------------

    public function test_destroy_returns_200_on_success(): void
    {
        $user = $this->buildUserEntity(10);

        $this->findUserService->method('find')->willReturn($user);
        $this->deleteUserService
            ->expects($this->once())
            ->method('execute');

        $response = $this->actingAsUser()->deleteJson('/api/users/10');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'User deleted successfully');
    }

    // -------------------------------------------------------------------------
    // POST /api/users/{user}/assign-role
    // -------------------------------------------------------------------------

    public function test_assign_role_returns_200(): void
    {
        $user = $this->buildUserEntity(10);

        $this->findUserService->method('find')->willReturn($user);
        $this->assignRoleService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($user);

        $response = $this->actingAsUser()->postJson('/api/users/10/assign-role', [
            'role_id' => 5,
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/users/{user}/preferences
    // -------------------------------------------------------------------------

    public function test_update_user_preferences_returns_200(): void
    {
        $user = $this->buildUserEntity(10);

        $this->findUserService->method('find')->willReturn($user);
        $this->updatePreferencesService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($user);

        $response = $this->actingAsUser()->patchJson('/api/users/10/preferences', [
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    // -------------------------------------------------------------------------
    // GET /api/profile
    // -------------------------------------------------------------------------

    public function test_profile_show_returns_authenticated_user(): void
    {
        $user = $this->buildUserEntity(99);

        $this->findUserService
            ->expects($this->once())
            ->method('find')
            ->with(99)
            ->willReturn($user);

        $response = $this->actingAsUser()->getJson('/api/profile');

        // ProfileController wraps in Response::json(), so no 'data' key
        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('id', 99);
    }

    public function test_profile_show_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/profile')->assertStatus(HttpResponse::HTTP_UNAUTHORIZED);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/profile
    // -------------------------------------------------------------------------

    public function test_profile_update_returns_200(): void
    {
        $user = $this->buildUserEntity(99);

        $this->updateProfileService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($user);

        $response = $this->actingAsUser()->patchJson('/api/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    // -------------------------------------------------------------------------
    // POST /api/profile/change-password
    // -------------------------------------------------------------------------

    public function test_change_password_returns_200_on_success(): void
    {
        $this->changePasswordService
            ->expects($this->once())
            ->method('execute');

        $response = $this->actingAsUser()->postJson('/api/profile/change-password', [
            'current_password' => 'OldPass1!',
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Password changed successfully.');
    }

    public function test_change_password_returns_422_on_missing_fields(): void
    {
        $response = $this->actingAsUser()->postJson('/api/profile/change-password', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/profile/preferences
    // -------------------------------------------------------------------------

    public function test_profile_update_preferences_returns_200(): void
    {
        $user = $this->buildUserEntity(99);

        $this->updatePreferencesService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($user);

        $response = $this->actingAsUser()->patchJson('/api/profile/preferences', [
            'language' => 'fr',
            'timezone' => 'Europe/Paris',
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    // -------------------------------------------------------------------------
    // GET /api/roles
    // -------------------------------------------------------------------------

    public function test_roles_index_returns_paginated_list(): void
    {
        $role = $this->buildRoleEntity();
        $paginator = new LengthAwarePaginator(collect([$role]), 1, 15, 1);

        $this->findRoleService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->actingAsUser()->getJson('/api/roles');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.name', 'manager');
    }

    // -------------------------------------------------------------------------
    // POST /api/roles
    // -------------------------------------------------------------------------

    public function test_store_role_returns_201(): void
    {
        $role = $this->buildRoleEntity();

        $this->createRoleService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($role);

        $response = $this->actingAsUser()->postJson('/api/roles', [
            'tenant_id' => 1,
            'name' => 'manager',
            'guard_name' => 'api',
        ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.name', 'manager');
    }

    public function test_store_role_returns_422_on_missing_fields(): void
    {
        $response = $this->actingAsUser()->postJson('/api/roles', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    // -------------------------------------------------------------------------
    // GET /api/roles/{role}
    // -------------------------------------------------------------------------

    public function test_show_role_returns_200(): void
    {
        $role = $this->buildRoleEntity(5);

        $this->findRoleService
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($role);

        $response = $this->actingAsUser()->getJson('/api/roles/5');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 5);
    }

    public function test_show_role_returns_404_when_not_found(): void
    {
        $this->findRoleService
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->actingAsUser()->getJson('/api/roles/999');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/roles/{role}
    // -------------------------------------------------------------------------

    public function test_destroy_role_returns_200(): void
    {
        $role = $this->buildRoleEntity(5);

        $this->findRoleService->method('find')->willReturn($role);
        $this->deleteRoleService
            ->expects($this->once())
            ->method('execute');

        $response = $this->actingAsUser()->deleteJson('/api/roles/5');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Role deleted successfully');
    }

    // -------------------------------------------------------------------------
    // PUT /api/roles/{role}/permissions
    // -------------------------------------------------------------------------

    public function test_sync_role_permissions_returns_200(): void
    {
        $role = $this->buildRoleEntity(5);

        $this->findRoleService->method('find')->willReturn($role);
        $this->syncRolePermissionsService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($role);

        $response = $this->actingAsUser()->putJson('/api/roles/5/permissions', [
            'permission_ids' => [1, 2, 3],
        ]);

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    // -------------------------------------------------------------------------
    // GET /api/permissions
    // -------------------------------------------------------------------------

    public function test_permissions_index_returns_paginated_list(): void
    {
        $permission = $this->buildPermissionEntity();
        $paginator = new LengthAwarePaginator(collect([$permission]), 1, 15, 1);

        $this->findPermissionService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->actingAsUser()->getJson('/api/permissions');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.name', 'users.view');
    }

    // -------------------------------------------------------------------------
    // POST /api/permissions
    // -------------------------------------------------------------------------

    public function test_store_permission_returns_201(): void
    {
        $permission = $this->buildPermissionEntity();

        $this->createPermissionService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($permission);

        $response = $this->actingAsUser()->postJson('/api/permissions', [
            'tenant_id' => 1,
            'name' => 'users.view',
            'guard_name' => 'api',
            'module' => 'User',
        ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.name', 'users.view');
    }

    public function test_store_permission_returns_422_on_missing_fields(): void
    {
        $response = $this->actingAsUser()->postJson('/api/permissions', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    // -------------------------------------------------------------------------
    // GET /api/permissions/{permission}
    // -------------------------------------------------------------------------

    public function test_show_permission_returns_200(): void
    {
        $permission = $this->buildPermissionEntity(3);

        $this->findPermissionService
            ->expects($this->once())
            ->method('find')
            ->with(3)
            ->willReturn($permission);

        $response = $this->actingAsUser()->getJson('/api/permissions/3');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 3);
    }

    public function test_show_permission_returns_404_when_not_found(): void
    {
        $this->findPermissionService
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->actingAsUser()->getJson('/api/permissions/999');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/permissions/{permission}
    // -------------------------------------------------------------------------

    public function test_destroy_permission_returns_200(): void
    {
        $permission = $this->buildPermissionEntity(3);

        $this->findPermissionService->method('find')->willReturn($permission);
        $this->deletePermissionService
            ->expects($this->once())
            ->method('execute');

        $response = $this->actingAsUser()->deleteJson('/api/permissions/3');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Permission deleted successfully');
    }

    private function preparePassportKeys(): void
    {
        if (self::$passportKeysPrepared) {
            return;
        }

        Artisan::call('passport:keys', ['--force' => true]);

        self::$passportKeysPrepared = true;
    }
}
