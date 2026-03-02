<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Illuminate\Pipeline\Pipeline;
use Modules\Accounting\Application\Commands\UpdateAccountCommand;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Enums\AccountStatus;
use Modules\Core\Application\Pipes\AuditLogPipe;
use Modules\Core\Application\Pipes\ValidateCommandPipe;

class UpdateAccountHandler extends BaseHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly Pipeline $pipeline,
    ) {}

    public function handle(UpdateAccountCommand $command): Account
    {
        return $this->transaction(function () use ($command): Account {
            $account = $this->accountRepository->findById($command->id, $command->tenantId);

            if ($account === null) {
                throw new \DomainException("Account with ID {$command->id} not found.");
            }

            if ($account->isSystemAccount) {
                throw new \DomainException('System accounts cannot be updated.');
            }

            return $this->pipeline
                ->send($command)
                ->through([
                    ValidateCommandPipe::class,
                    AuditLogPipe::class,
                ])
                ->then(function (UpdateAccountCommand $cmd) use ($account): Account {
                    $updated = new Account(
                        id: $account->id,
                        tenantId: $account->tenantId,
                        parentId: $account->parentId,
                        code: $account->code,
                        name: $cmd->name,
                        type: $account->type,
                        status: AccountStatus::from($cmd->status),
                        description: $cmd->description,
                        isSystemAccount: $account->isSystemAccount,
                        openingBalance: $account->openingBalance,
                        currentBalance: $account->currentBalance,
                        createdAt: $account->createdAt,
                        updatedAt: null,
                    );

                    return $this->accountRepository->save($updated);
                });
        });
    }
}
