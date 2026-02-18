<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Mockery;
use Modules\Accounting\Entities\Account;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Services\AccountService;
use PHPUnit\Framework\TestCase;

class AccountServiceTest extends TestCase
{
    protected AccountService $accountService;

    protected $accountRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $this->accountService = new AccountService($this->accountRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_account_with_generated_number(): void
    {
        $data = [
            'name' => 'Test Account',
            'type' => 'asset',
            'currency' => 'USD',
        ];

        $account = new Account($data);
        $account->id = 1;
        $account->account_number = '11000';

        $this->accountRepository
            ->shouldReceive('getModel')
            ->andReturn(Mockery::mock(['where' => Mockery::self(), 'orderBy' => Mockery::self(), 'first' => null]));

        $this->accountRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($account);

        $result = $this->accountService->create($data);

        $this->assertInstanceOf(Account::class, $result);
        $this->assertEquals('Test Account', $result->name);
    }

    public function test_it_finds_account_by_id(): void
    {
        $account = new Account(['name' => 'Test Account']);
        $account->id = 1;

        $this->accountRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($account);

        $result = $this->accountService->findById(1);

        $this->assertEquals($account, $result);
    }
}
