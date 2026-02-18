<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\IAM\Models\Permission;
use App\Modules\IAM\Models\Role;
use Tests\TestCase;

/**
 * Base Feature Test Case
 *
 * Provides common functionality for feature tests including
 * tenant setup, authentication helpers, and API testing utilities.
 */
abstract class FeatureTestCase extends TestCase
{
    protected ?Tenant $tenant = null;

    protected ?User $user = null;

    /**
     * Setup the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a default tenant for tests
        $this->tenant = $this->createTenant();
    }

    /**
     * Create a tenant for testing.
     */
    protected function createTenant(array $attributes = []): Tenant
    {
        return Tenant::factory()->create($attributes);
    }

    /**
     * Create and authenticate a user for testing.
     */
    protected function actingAsUser(array $userAttributes = [], ?Tenant $tenant = null): User
    {
        $tenant = $tenant ?? $this->tenant;

        $this->user = User::factory()
            ->forTenant($tenant)
            ->create($userAttributes);

        $this->actingAs($this->user);

        return $this->user;
    }

    /**
     * Create and authenticate a user with specific role.
     */
    protected function actingAsUserWithRole(string $roleSlug, array $userAttributes = [], ?Tenant $tenant = null): User
    {
        $tenant = $tenant ?? $this->tenant;

        $role = Role::factory()
            ->forTenant($tenant)
            ->create(['slug' => $roleSlug]);

        $user = $this->actingAsUser($userAttributes, $tenant);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Create and authenticate a user with specific permissions.
     */
    protected function actingAsUserWithPermissions(array $permissions, array $userAttributes = [], ?Tenant $tenant = null): User
    {
        $tenant = $tenant ?? $this->tenant;

        $role = Role::factory()
            ->forTenant($tenant)
            ->create();

        foreach ($permissions as $permissionSlug) {
            $permission = Permission::factory()
                ->create(['slug' => $permissionSlug]);
            $role->grantPermission($permission);
        }

        $user = $this->actingAsUser($userAttributes, $tenant);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Create an admin user and authenticate.
     */
    protected function actingAsAdmin(?Tenant $tenant = null): User
    {
        return $this->actingAsUserWithRole('admin', ['name' => 'Admin User'], $tenant);
    }

    /**
     * Make an authenticated API request.
     */
    protected function apiAs(User $user, string $method, string $uri, array $data = [], array $headers = [])
    {
        return $this->actingAs($user)->json($method, $uri, $data, $headers);
    }

    /**
     * Make a GET request to an API endpoint.
     */
    public function getJson($uri, array $headers = [], $options = 0)
    {
        return parent::getJson($uri, array_merge([
            'Accept' => 'application/json',
        ], $headers), $options);
    }

    /**
     * Make a POST request to an API endpoint.
     */
    public function postJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::postJson($uri, $data, array_merge([
            'Accept' => 'application/json',
        ], $headers), $options);
    }

    /**
     * Make a PUT request to an API endpoint.
     */
    public function putJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::putJson($uri, $data, array_merge([
            'Accept' => 'application/json',
        ], $headers), $options);
    }

    /**
     * Make a PATCH request to an API endpoint.
     */
    public function patchJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::patchJson($uri, $data, array_merge([
            'Accept' => 'application/json',
        ], $headers), $options);
    }

    /**
     * Make a DELETE request to an API endpoint.
     */
    public function deleteJson($uri, array $data = [], array $headers = [], $options = 0)
    {
        return parent::deleteJson($uri, $data, array_merge([
            'Accept' => 'application/json',
        ], $headers), $options);
    }

    /**
     * Assert JSON response has success structure.
     */
    protected function assertJsonSuccess($response, int $status = 200)
    {
        $response->assertStatus($status)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
            ]);

        return $response;
    }

    /**
     * Assert JSON response has error structure.
     */
    protected function assertJsonError($response, int $status = 400)
    {
        $response->assertStatus($status)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => false,
            ]);

        return $response;
    }

    /**
     * Assert validation errors in response.
     */
    protected function assertValidationErrors($response, array $fields)
    {
        $response->assertStatus(422)
            ->assertJsonValidationErrors($fields);

        return $response;
    }

    /**
     * Get tenant for tests.
     */
    protected function getTenant(): Tenant
    {
        return $this->tenant;
    }

    /**
     * Get authenticated user.
     */
    protected function getUser(): ?User
    {
        return $this->user;
    }
}
