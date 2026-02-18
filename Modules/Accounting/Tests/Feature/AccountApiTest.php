<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Entities\Account;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_list_accounts(): void
    {
        Account::factory()->count(3)->create(['tenant_id' => 1]);

        $response = $this->getJson('/api/accounting/v1/accounts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'account_number',
                    'name',
                    'type',
                    'balance',
                ],
            ],
        ]);
    }

    public function test_it_can_create_account(): void
    {
        $data = [
            'name' => 'Test Account',
            'type' => 'asset',
            'currency' => 'USD',
        ];

        $response = $this->postJson('/api/accounting/v1/accounts', $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'account_number',
                'name',
                'type',
            ],
        ]);

        $this->assertDatabaseHas('accounts', [
            'name' => 'Test Account',
            'type' => 'asset',
        ]);
    }

    public function test_it_can_show_account(): void
    {
        $account = Account::factory()->create(['tenant_id' => 1]);

        $response = $this->getJson("/api/accounting/v1/accounts/{$account->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $account->id,
                'name' => $account->name,
            ],
        ]);
    }

    public function test_it_can_update_account(): void
    {
        $account = Account::factory()->create(['tenant_id' => 1]);

        $data = [
            'name' => 'Updated Account',
        ];

        $response = $this->putJson("/api/accounting/v1/accounts/{$account->id}", $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Updated Account',
        ]);
    }

    public function test_it_can_delete_account(): void
    {
        $account = Account::factory()->create(['tenant_id' => 1, 'is_system' => false]);

        $response = $this->deleteJson("/api/accounting/v1/accounts/{$account->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }
}
