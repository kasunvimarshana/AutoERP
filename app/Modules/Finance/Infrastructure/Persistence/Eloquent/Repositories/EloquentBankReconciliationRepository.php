<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\BankReconciliation;
use Modules\Finance\Domain\RepositoryInterfaces\BankReconciliationRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankReconciliationModel;

class EloquentBankReconciliationRepository extends EloquentRepository implements BankReconciliationRepositoryInterface
{
    public function __construct(BankReconciliationModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (BankReconciliationModel $m): BankReconciliation => $this->mapToDomain($m));
    }

    public function save(BankReconciliation $br): BankReconciliation
    {
        $data = [
            'tenant_id' => $br->getTenantId(),
            'bank_account_id' => $br->getBankAccountId(),
            'period_start' => $br->getPeriodStart()->format('Y-m-d'),
            'period_end' => $br->getPeriodEnd()->format('Y-m-d'),
            'opening_balance' => $br->getOpeningBalance(),
            'closing_balance' => $br->getClosingBalance(),
            'status' => $br->getStatus(),
            'completed_by' => $br->getCompletedBy(),
            'completed_at' => $br->getCompletedAt()?->format('Y-m-d H:i:s'),
        ];

        $model = $br->getId() ? $this->update($br->getId(), $data) : $this->create($data);

        /** @var BankReconciliationModel $model */
        return $this->toDomainEntity($model);
    }

    private function mapToDomain(BankReconciliationModel $m): BankReconciliation
    {
        return new BankReconciliation(
            tenantId: (int) $m->tenant_id,
            bankAccountId: (int) $m->bank_account_id,
            periodStart: $m->period_start,
            periodEnd: $m->period_end,
            openingBalance: (float) $m->opening_balance,
            closingBalance: (float) $m->closing_balance,
            status: (string) $m->status,
            completedBy: $m->completed_by !== null ? (int) $m->completed_by : null,
            completedAt: $m->completed_at,
            rowVersion: (int) ($m->row_version ?? 1),
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
