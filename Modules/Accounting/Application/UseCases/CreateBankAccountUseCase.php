<?php

namespace Modules\Accounting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\BankAccountRepositoryInterface;
use Modules\Accounting\Domain\Events\BankAccountCreated;

class CreateBankAccountUseCase
{
    public function __construct(
        private BankAccountRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            if (empty(trim((string) ($data['name'] ?? '')))) {
                throw new \DomainException('Bank account name is required.');
            }

            if (empty(trim((string) ($data['account_number'] ?? '')))) {
                throw new \DomainException('Account number is required.');
            }

            $account = $this->repo->create(array_merge($data, [
                'is_active' => $data['is_active'] ?? true,
            ]));

            Event::dispatch(new BankAccountCreated($account->id, $data['tenant_id'] ?? null, $account->name));

            return $account;
        });
    }
}
