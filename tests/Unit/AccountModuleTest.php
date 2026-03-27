<?php

namespace Tests\Unit;

use Modules\Account\Application\Contracts\CreateAccountServiceInterface;
use Modules\Account\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Account\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Account\Application\DTOs\AccountData;
use Modules\Account\Application\Services\CreateAccountService;
use Modules\Account\Application\Services\DeleteAccountService;
use Modules\Account\Application\Services\UpdateAccountService;
use Modules\Account\Application\UseCases\CreateAccount;
use Modules\Account\Application\UseCases\DeleteAccount;
use Modules\Account\Application\UseCases\GetAccount;
use Modules\Account\Application\UseCases\ListAccounts;
use Modules\Account\Application\UseCases\UpdateAccount;
use Modules\Account\Domain\Entities\Account;
use Modules\Account\Domain\Events\AccountCreated;
use Modules\Account\Domain\Events\AccountDeleted;
use Modules\Account\Domain\Events\AccountUpdated;
use Modules\Account\Domain\Exceptions\AccountNotFoundException;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Account\Infrastructure\Http\Controllers\AccountController;
use Modules\Account\Infrastructure\Http\Requests\StoreAccountRequest;
use Modules\Account\Infrastructure\Http\Requests\UpdateAccountRequest;
use Modules\Account\Infrastructure\Http\Resources\AccountCollection;
use Modules\Account\Infrastructure\Http\Resources\AccountResource;
use Modules\Account\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Account\Infrastructure\Persistence\Eloquent\Repositories\EloquentAccountRepository;
use Modules\Account\Infrastructure\Providers\AccountServiceProvider;
use PHPUnit\Framework\TestCase;

class AccountModuleTest extends TestCase
{
    // ── Domain Entities ───────────────────────────────────────────────────────

    public function test_account_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Account::class));
    }

    public function test_account_entity_can_be_constructed(): void
    {
        $account = new Account(
            tenantId: 1,
            code: '1000',
            name: 'Cash',
            type: 'asset',
        );

        $this->assertSame(1, $account->getTenantId());
        $this->assertSame('1000', $account->getCode());
        $this->assertSame('Cash', $account->getName());
        $this->assertSame('asset', $account->getType());
        $this->assertSame('active', $account->getStatus());
        $this->assertSame('USD', $account->getCurrency());
        $this->assertSame(0.0, $account->getBalance());
        $this->assertFalse($account->isSystem());
        $this->assertNull($account->getSubtype());
        $this->assertNull($account->getDescription());
        $this->assertNull($account->getParentId());
        $this->assertNull($account->getAttributes());
        $this->assertNull($account->getMetadata());
        $this->assertNull($account->getId());
    }

    public function test_account_entity_with_all_fields(): void
    {
        $account = new Account(
            tenantId: 2,
            code: '1100',
            name: 'Accounts Receivable',
            type: 'asset',
            subtype: 'current_asset',
            description: 'Money owed by customers',
            currency: 'EUR',
            balance: 5000.0,
            isSystem: true,
            parentId: 1,
            status: 'inactive',
            attributes: ['normal_balance' => 'debit'],
            metadata: ['source' => 'import'],
            id: 42,
        );

        $this->assertSame(42, $account->getId());
        $this->assertSame(2, $account->getTenantId());
        $this->assertSame('1100', $account->getCode());
        $this->assertSame('Accounts Receivable', $account->getName());
        $this->assertSame('asset', $account->getType());
        $this->assertSame('current_asset', $account->getSubtype());
        $this->assertSame('Money owed by customers', $account->getDescription());
        $this->assertSame('EUR', $account->getCurrency());
        $this->assertSame(5000.0, $account->getBalance());
        $this->assertTrue($account->isSystem());
        $this->assertSame(1, $account->getParentId());
        $this->assertSame('inactive', $account->getStatus());
        $this->assertSame(['normal_balance' => 'debit'], $account->getAttributes());
        $this->assertSame(['source' => 'import'], $account->getMetadata());
    }

    public function test_account_entity_update_details(): void
    {
        $account = new Account(tenantId: 1, code: '1000', name: 'Old Name', type: 'asset');

        $account->updateDetails(
            code: '1001',
            name: 'New Name',
            type: 'liability',
            subtype: 'current_liability',
            description: 'Updated description',
            currency: 'GBP',
            parentId: 5,
            attributes: ['key' => 'value'],
            metadata: ['updated' => true],
        );

        $this->assertSame('1001', $account->getCode());
        $this->assertSame('New Name', $account->getName());
        $this->assertSame('liability', $account->getType());
        $this->assertSame('current_liability', $account->getSubtype());
        $this->assertSame('Updated description', $account->getDescription());
        $this->assertSame('GBP', $account->getCurrency());
        $this->assertSame(5, $account->getParentId());
        $this->assertSame(['key' => 'value'], $account->getAttributes());
        $this->assertSame(['updated' => true], $account->getMetadata());
    }

    public function test_account_entity_activate_deactivate(): void
    {
        $account = new Account(tenantId: 1, code: '1000', name: 'Test', type: 'asset', status: 'inactive');
        $this->assertFalse($account->isActive());

        $account->activate();
        $this->assertTrue($account->isActive());
        $this->assertSame('active', $account->getStatus());

        $account->deactivate();
        $this->assertFalse($account->isActive());
        $this->assertSame('inactive', $account->getStatus());
    }

    public function test_account_entity_adjust_balance(): void
    {
        $account = new Account(tenantId: 1, code: '1000', name: 'Cash', type: 'asset', balance: 1000.0);

        $account->adjustBalance(500.0);
        $this->assertSame(1500.0, $account->getBalance());

        $account->adjustBalance(-200.0);
        $this->assertSame(1300.0, $account->getBalance());
    }

    public function test_account_entity_type_helpers(): void
    {
        $asset = new Account(tenantId: 1, code: '1000', name: 'Cash', type: 'asset');
        $this->assertTrue($asset->isAsset());
        $this->assertFalse($asset->isLiability());
        $this->assertFalse($asset->isEquity());
        $this->assertFalse($asset->isIncome());
        $this->assertFalse($asset->isExpense());

        $liability = new Account(tenantId: 1, code: '2000', name: 'AP', type: 'liability');
        $this->assertTrue($liability->isLiability());

        $equity = new Account(tenantId: 1, code: '3000', name: 'Capital', type: 'equity');
        $this->assertTrue($equity->isEquity());

        $income = new Account(tenantId: 1, code: '4000', name: 'Sales', type: 'income');
        $this->assertTrue($income->isIncome());

        $expense = new Account(tenantId: 1, code: '5000', name: 'COGS', type: 'expense');
        $this->assertTrue($expense->isExpense());
    }

    // ── Domain Events ─────────────────────────────────────────────────────────

    public function test_all_account_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(AccountCreated::class));
        $this->assertTrue(class_exists(AccountUpdated::class));
        $this->assertTrue(class_exists(AccountDeleted::class));
    }

    public function test_account_created_event_can_be_instantiated(): void
    {
        $account = new Account(tenantId: 1, code: '1000', name: 'Cash', type: 'asset', id: 1);
        $event = new AccountCreated($account);

        $this->assertSame($account, $event->account);
        $this->assertSame(1, $event->tenantId);
    }

    public function test_account_updated_event_can_be_instantiated(): void
    {
        $account = new Account(tenantId: 1, code: '1000', name: 'Cash', type: 'asset', id: 1);
        $event = new AccountUpdated($account);

        $this->assertSame($account, $event->account);
        $this->assertSame(1, $event->tenantId);
    }

    public function test_account_deleted_event_can_be_instantiated(): void
    {
        $event = new AccountDeleted(accountId: 5, tenantId: 1);

        $this->assertSame(5, $event->accountId);
        $this->assertSame(1, $event->tenantId);
    }

    public function test_account_created_event_broadcast_with_contains_required_fields(): void
    {
        $account = new Account(tenantId: 1, code: '1000', name: 'Cash', type: 'asset', id: 1);
        $event = new AccountCreated($account);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('code', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertArrayHasKey('status', $payload);
    }

    // ── Domain Exceptions ─────────────────────────────────────────────────────

    public function test_account_not_found_exception_class_exists(): void
    {
        $this->assertTrue(class_exists(AccountNotFoundException::class));
    }

    public function test_account_not_found_exception_can_be_instantiated(): void
    {
        $exception = new AccountNotFoundException(99);

        $this->assertStringContainsString('Account', $exception->getMessage());
        $this->assertStringContainsString('99', $exception->getMessage());
    }

    // ── Domain Repository Interface ───────────────────────────────────────────

    public function test_account_repository_interface_exists(): void
    {
        $this->assertTrue(interface_exists(AccountRepositoryInterface::class));
    }

    public function test_account_repository_interface_has_required_methods(): void
    {
        $reflection = new \ReflectionClass(AccountRepositoryInterface::class);

        $this->assertTrue($reflection->hasMethod('findByCode'));
        $this->assertTrue($reflection->hasMethod('findByTenant'));
        $this->assertTrue($reflection->hasMethod('findByType'));
        $this->assertTrue($reflection->hasMethod('save'));
    }

    // ── Application DTOs ──────────────────────────────────────────────────────

    public function test_account_data_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(AccountData::class));
    }

    public function test_account_data_dto_has_rules(): void
    {
        $dto = new AccountData;
        $rules = $dto->rules();

        $this->assertArrayHasKey('tenant_id', $rules);
        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('type', $rules);
        $this->assertArrayHasKey('status', $rules);
        $this->assertArrayHasKey('currency', $rules);
    }

    public function test_account_data_dto_can_be_created_from_array(): void
    {
        $dto = AccountData::fromArray([
            'tenant_id' => 1,
            'code'      => '1000',
            'name'      => 'Cash',
            'type'      => 'asset',
            'currency'  => 'USD',
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame('1000', $dto->code);
        $this->assertSame('Cash', $dto->name);
        $this->assertSame('asset', $dto->type);
        $this->assertSame('USD', $dto->currency);
    }

    public function test_account_data_dto_to_array_contains_expected_keys(): void
    {
        $dto = AccountData::fromArray([
            'tenant_id' => 1,
            'code'      => '1000',
            'name'      => 'Cash',
            'type'      => 'asset',
        ]);
        $array = $dto->toArray();

        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('type', $array);
    }

    // ── Application Service Contracts ─────────────────────────────────────────

    public function test_all_account_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateAccountServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateAccountServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteAccountServiceInterface::class));
    }

    // ── Application Services ──────────────────────────────────────────────────

    public function test_all_account_service_implementations_exist(): void
    {
        $this->assertTrue(class_exists(CreateAccountService::class));
        $this->assertTrue(class_exists(UpdateAccountService::class));
        $this->assertTrue(class_exists(DeleteAccountService::class));
    }

    public function test_account_service_implementations_implement_their_interfaces(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateAccountService::class, CreateAccountServiceInterface::class),
            'CreateAccountService must implement CreateAccountServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(UpdateAccountService::class, UpdateAccountServiceInterface::class),
            'UpdateAccountService must implement UpdateAccountServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeleteAccountService::class, DeleteAccountServiceInterface::class),
            'DeleteAccountService must implement DeleteAccountServiceInterface.'
        );
    }

    // ── Application Use Cases ─────────────────────────────────────────────────

    public function test_all_account_use_case_classes_exist(): void
    {
        $this->assertTrue(class_exists(CreateAccount::class));
        $this->assertTrue(class_exists(UpdateAccount::class));
        $this->assertTrue(class_exists(DeleteAccount::class));
        $this->assertTrue(class_exists(GetAccount::class));
        $this->assertTrue(class_exists(ListAccounts::class));
    }

    // ── Infrastructure – Models ───────────────────────────────────────────────

    public function test_account_eloquent_model_class_exists(): void
    {
        $this->assertTrue(class_exists(AccountModel::class));
    }

    public function test_account_model_has_expected_fillable(): void
    {
        $model = new AccountModel;
        $fillable = $model->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('currency', $fillable);
        $this->assertContains('balance', $fillable);
        $this->assertContains('is_system', $fillable);
        $this->assertContains('status', $fillable);
    }

    // ── Infrastructure – Repositories ─────────────────────────────────────────

    public function test_account_eloquent_repository_exists(): void
    {
        $this->assertTrue(class_exists(EloquentAccountRepository::class));
    }

    public function test_account_eloquent_repository_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentAccountRepository::class, AccountRepositoryInterface::class),
            'EloquentAccountRepository must implement AccountRepositoryInterface.'
        );
    }

    // ── Infrastructure – HTTP ─────────────────────────────────────────────────

    public function test_account_controller_class_exists(): void
    {
        $this->assertTrue(class_exists(AccountController::class));
    }

    public function test_account_form_request_classes_exist(): void
    {
        $this->assertTrue(class_exists(StoreAccountRequest::class));
        $this->assertTrue(class_exists(UpdateAccountRequest::class));
    }

    public function test_account_resource_classes_exist(): void
    {
        $this->assertTrue(class_exists(AccountResource::class));
        $this->assertTrue(class_exists(AccountCollection::class));
    }

    // ── Infrastructure – Provider ─────────────────────────────────────────────

    public function test_account_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(AccountServiceProvider::class));
    }

    // ── Domain behaviour: timestamps ─────────────────────────────────────────

    public function test_account_entity_timestamps_are_set_on_construction(): void
    {
        $before = new \DateTimeImmutable;
        $account = new Account(tenantId: 1, code: '1000', name: 'Cash', type: 'asset');
        $after = new \DateTimeImmutable;

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $account->getCreatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $account->getCreatedAt()->getTimestamp());
    }

    public function test_account_entity_updated_at_changes_on_update_details(): void
    {
        $account = new Account(tenantId: 1, code: '1000', name: 'Old', type: 'asset');
        $originalUpdatedAt = $account->getUpdatedAt();

        usleep(1000);

        $account->updateDetails('1001', 'New', 'liability', null, null, 'USD', null, null, null);

        $this->assertGreaterThanOrEqual(
            $originalUpdatedAt->getTimestamp(),
            $account->getUpdatedAt()->getTimestamp()
        );
    }

    // ── Store request rules ────────────────────────────────────────────────────

    public function test_store_account_request_has_required_rules(): void
    {
        $request = new StoreAccountRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('tenant_id', $rules);
        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('type', $rules);
        $this->assertArrayHasKey('status', $rules);
    }

    public function test_update_account_request_has_required_rules(): void
    {
        $request = new UpdateAccountRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('code', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('type', $rules);
        $this->assertArrayHasKey('status', $rules);
    }
}
