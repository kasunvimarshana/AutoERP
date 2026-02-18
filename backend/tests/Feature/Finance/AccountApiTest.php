<?php

namespace Tests\Feature\Finance;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUri = '/api/v1/finance/accounts';

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($this->user);
    }

    public function test_can_create_account(): void
    {
        $data = [
            'code' => '1000',
            'name' => 'Cash',
            'type' => AccountType::ASSET->value,
            'opening_balance' => 5000.00,
            'description' => 'Cash account',
        ];

        $response = $this->postJson($this->baseUri, $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'type',
                    'opening_balance',
                ],
            ]);

        $this->assertDatabaseHas('accounts', [
            'code' => '1000',
            'name' => 'Cash',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_list_accounts(): void
    {
        Account::factory()->forTenant($this->tenant)->count(3)->create();

        $response = $this->getJson($this->baseUri);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => ['id', 'code', 'name', 'type'],
                    ],
                ],
            ]);
    }

    public function test_can_get_single_account(): void
    {
        $account = Account::factory()->forTenant($this->tenant)->create();

        $response = $this->getJson("{$this->baseUri}/{$account->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $account->id,
                    'code' => $account->code,
                ],
            ]);
    }

    public function test_can_update_account(): void
    {
        $account = Account::factory()->forTenant($this->tenant)->create();

        $data = [
            'code' => '1001',
            'name' => 'Updated Cash',
            'type' => $account->type->value,
            'opening_balance' => 7000.00,
        ];

        $response = $this->putJson("{$this->baseUri}/{$account->id}", $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'code' => '1001',
            'name' => 'Updated Cash',
        ]);
    }

    public function test_can_delete_account(): void
    {
        $account = Account::factory()->forTenant($this->tenant)->create();

        $response = $this->deleteJson("{$this->baseUri}/{$account->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    public function test_can_get_accounts_by_type(): void
    {
        Account::factory()->forTenant($this->tenant)->asset()->count(2)->create();
        Account::factory()->forTenant($this->tenant)->liability()->count(1)->create();

        $response = $this->getJson("{$this->baseUri}/by-type?type=".AccountType::ASSET->value);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_account_balance(): void
    {
        $account = Account::factory()->forTenant($this->tenant)->create([
            'opening_balance' => 1000.00,
        ]);

        $response = $this->getJson("{$this->baseUri}/{$account->id}/balance");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'balance' => 1000.00,
                ],
            ]);
    }
}
