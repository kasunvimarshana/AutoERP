<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use Modules\Finance\Application\Services\UpdateAccountService;
use Modules\Finance\Domain\Exceptions\AccountNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    /** @var AccountRepositoryInterface&MockObject */
    private AccountRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(AccountRepositoryInterface::class);
    }

    public function test_update_account_service_throws_when_account_missing(): void
    {
        $service = new UpdateAccountService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(AccountNotFoundException::class);

        $service->execute([
            'id' => 999,
            'tenant_id' => 1,
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'normal_balance' => 'debit',
        ]);
    }
}
