<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Accounting\Application\Commands\CreateAccountCommand;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Enums\AccountStatus;
use Modules\Accounting\Domain\Enums\AccountType;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;

class CreateAccountHandler extends BaseHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(CreateAccountCommand $command): Account
    {
        return $this->transaction(function () use ($command): Account {
            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (CreateAccountCommand $cmd): Account {
                    if ($this->accountRepository->findByCode($cmd->code, $cmd->tenantId) !== null) {
                        throw new \DomainException("Account code '{$cmd->code}' already exists for this tenant.");
                    }

                    if ($cmd->parentId !== null) {
                        $parent = $this->accountRepository->findById($cmd->parentId, $cmd->tenantId);
                        if ($parent === null) {
                            throw new \DomainException("Parent account with ID {$cmd->parentId} not found.");
                        }
                    }

                    $openingBalance = bcadd((string) $cmd->openingBalance, '0', 4);

                    $account = new Account(
                        id: null,
                        tenantId: $cmd->tenantId,
                        parentId: $cmd->parentId,
                        code: $cmd->code,
                        name: $cmd->name,
                        type: AccountType::from($cmd->type),
                        status: AccountStatus::Active,
                        description: $cmd->description,
                        isSystemAccount: false,
                        openingBalance: $openingBalance,
                        currentBalance: $openingBalance,
                        createdAt: null,
                        updatedAt: null,
                    );

                    return $this->accountRepository->save($account);
                });
        });
    }
}
