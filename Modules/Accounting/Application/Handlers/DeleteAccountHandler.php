<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Handlers;

use App\Shared\Abstractions\BaseHandler;
use Modules\Accounting\Application\Commands\DeleteAccountCommand;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;

class DeleteAccountHandler extends BaseHandler
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
    ) {}

    public function handle(DeleteAccountCommand $command): void
    {
        $this->transaction(function () use ($command): void {
            $account = $this->accountRepository->findById($command->id, $command->tenantId);

            if ($account === null) {
                throw new \DomainException("Account with ID {$command->id} not found.");
            }

            if ($account->isSystemAccount) {
                throw new \DomainException('System accounts cannot be deleted.');
            }

            $this->accountRepository->delete($command->id, $command->tenantId);
        });
    }
}
