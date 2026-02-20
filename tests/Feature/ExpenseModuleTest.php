<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $tenant->id]);
    }

    public function test_unauthenticated_cannot_list_expense_categories(): void
    {
        $this->getJson('/api/v1/expenses/categories')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_expense_categories(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/expenses/categories')
            ->assertStatus(200);
    }

    public function test_unauthenticated_cannot_list_expenses(): void
    {
        $this->getJson('/api/v1/expenses')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_create_expense(): void
    {
        $this->postJson('/api/v1/expenses', [])->assertStatus(401);
    }
}
