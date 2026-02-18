<?php

namespace Tests\Unit\Finance;

use App\Models\Tenant;
use App\Modules\Finance\DTOs\CreateAccountDTO;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Repositories\AccountRepository;
use App\Modules\Finance\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountService $service;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->service = new AccountService(new AccountRepository);
        $this->actingAs($this->createUser($this->tenant));
    }

    public function test_can_create_account(): void
    {
        $dto = new CreateAccountDTO(
            code: '1000',
            name: 'Cash',
            type: AccountType::ASSET,
            openingBalance: 5000.00
        );

        $account = $this->service->createAccount($dto);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals('1000', $account->code);
        $this->assertEquals('Cash', $account->name);
        $this->assertEquals(AccountType::ASSET, $account->type);
        $this->assertEquals(5000.00, $account->opening_balance);
    }

    public function test_cannot_create_duplicate_account_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Account::factory()->forTenant($this->tenant)->create(['code' => '1000']);

        $dto = new CreateAccountDTO(
            code: '1000',
            name: 'Duplicate',
            type: AccountType::ASSET
        );

        $this->service->createAccount($dto);
    }

    public function test_can_create_child_account(): void
    {
        $parent = Account::factory()->forTenant($this->tenant)->asset()->create([
            'code' => '1000',
            'name' => 'Assets',
        ]);

        $dto = new CreateAccountDTO(
            code: '1010',
            name: 'Current Assets',
            type: AccountType::ASSET,
            parentId: $parent->id
        );

        $child = $this->service->createAccount($dto);

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertEquals(AccountType::ASSET, $child->type);
    }

    public function test_cannot_create_child_with_different_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('same type');

        $parent = Account::factory()->forTenant($this->tenant)->asset()->create();

        $dto = new CreateAccountDTO(
            code: '2000',
            name: 'Revenue',
            type: AccountType::REVENUE,
            parentId: $parent->id
        );

        $this->service->createAccount($dto);
    }

    public function test_can_get_account_balance(): void
    {
        $account = Account::factory()->forTenant($this->tenant)->asset()->create([
            'opening_balance' => 1000.00,
        ]);

        $balance = $this->service->getAccountBalance($account->id);

        $this->assertEquals(1000.00, $balance);
    }

    public function test_cannot_delete_account_with_children(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('child accounts');

        $parent = Account::factory()->forTenant($this->tenant)->asset()->create();
        Account::factory()->forTenant($this->tenant)->withParent($parent)->create();

        $this->service->deleteAccount($parent->id);
    }

    protected function createUser(Tenant $tenant)
    {
        return \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);
    }
}
