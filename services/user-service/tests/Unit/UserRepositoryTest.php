<?php

namespace Tests\Unit;

use App\DTOs\UserDTO;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;
    private Tenant         $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UserRepository();
        $this->tenant     = Tenant::factory()->create(['status' => 'active']);
    }

    /*
    |--------------------------------------------------------------------------
    | findById
    |--------------------------------------------------------------------------
    */

    public function test_find_by_id_returns_user_in_tenant(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $result = $this->repository->findById($user->id, $this->tenant->id);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_find_by_id_returns_null_for_other_tenant(): void
    {
        $other = Tenant::factory()->create();
        $user  = User::factory()->create(['tenant_id' => $other->id]);

        $result = $this->repository->findById($user->id, $this->tenant->id);

        $this->assertNull($result);
    }

    /*
    |--------------------------------------------------------------------------
    | findByEmail
    |--------------------------------------------------------------------------
    */

    public function test_find_by_email(): void
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email'     => 'unique@example.com',
        ]);

        $result = $this->repository->findByEmail('unique@example.com', $this->tenant->id);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_find_by_email_returns_null_for_different_tenant(): void
    {
        $other = Tenant::factory()->create();
        User::factory()->create(['tenant_id' => $other->id, 'email' => 'x@example.com']);

        $this->assertNull($this->repository->findByEmail('x@example.com', $this->tenant->id));
    }

    /*
    |--------------------------------------------------------------------------
    | create
    |--------------------------------------------------------------------------
    */

    public function test_create_persists_user(): void
    {
        $dto = new UserDTO(
            tenantId:    $this->tenant->id,
            keycloakId:  null,
            name:        'Created User',
            email:       'create@example.com',
            username:    'created',
            role:        'staff',
            status:      'active',
            profile:     [],
            permissions: [],
            metadata:    [],
        );

        $user = $this->repository->create($dto);

        $this->assertDatabaseHas('users', [
            'email'     => 'create@example.com',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertEquals('create@example.com', $user->email);
    }

    /*
    |--------------------------------------------------------------------------
    | paginate
    |--------------------------------------------------------------------------
    */

    public function test_paginate_returns_only_tenant_users(): void
    {
        User::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);
        User::factory()->count(3)->create(); // other tenants

        $result = $this->repository->paginate($this->tenant->id, 15);

        $this->assertEquals(5, $result->total());
    }

    public function test_paginate_respects_search(): void
    {
        User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Alice']);
        User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Bob']);

        $result = $this->repository->paginate($this->tenant->id, 15, [], 'created_at', 'desc', 'Alice');

        $this->assertEquals(1, $result->total());
    }

    /*
    |--------------------------------------------------------------------------
    | delete & restore
    |--------------------------------------------------------------------------
    */

    public function test_soft_delete_and_restore(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->repository->delete($user->id, $this->tenant->id);
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->repository->restore($user->id, $this->tenant->id);
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | countByTenant
    |--------------------------------------------------------------------------
    */

    public function test_count_by_tenant(): void
    {
        User::factory()->count(4)->create(['tenant_id' => $this->tenant->id]);
        User::factory()->count(2)->create(); // other tenant

        $this->assertEquals(4, $this->repository->countByTenant($this->tenant->id));
    }
}
