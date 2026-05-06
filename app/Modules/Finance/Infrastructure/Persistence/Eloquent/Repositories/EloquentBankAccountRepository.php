<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\BankAccount;
use Modules\Finance\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;

class EloquentBankAccountRepository extends EloquentRepository implements BankAccountRepositoryInterface
{
    public function __construct(BankAccountModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (BankAccountModel $m): BankAccount => $this->mapToDomain($m));
    }

    public function save(BankAccount $ba): BankAccount
    {
        $data = [
            'tenant_id' => $ba->getTenantId(),
            'account_id' => $ba->getAccountId(),
            'name' => $ba->getName(),
            'bank_name' => $ba->getBankName(),
            'account_number' => $ba->getAccountNumber(),
            'routing_number' => $ba->getRoutingNumber(),
            'currency_id' => $ba->getCurrencyId(),
            'current_balance' => $ba->getCurrentBalance(),
            'feed_provider' => $ba->getFeedProvider(),
            'is_active' => $ba->isActive(),
        ];

        $model = $ba->getId() ? $this->update($ba->getId(), $data) : $this->create($data);

        /** @var BankAccountModel $model */
        return $this->toDomainEntity($model);
    }

    private function mapToDomain(BankAccountModel $m): BankAccount
    {
        return new BankAccount(
            tenantId: (int) $m->tenant_id,
            accountId: (int) $m->account_id,
            name: (string) $m->name,
            bankName: (string) $m->bank_name,
            accountNumber: (string) $m->account_number,
            currencyId: (int) $m->currency_id,
            routingNumber: $m->routing_number,
            currentBalance: (float) $m->current_balance,
            lastSyncAt: $m->last_sync_at,
            feedProvider: $m->feed_provider,
            isActive: (bool) $m->is_active,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
