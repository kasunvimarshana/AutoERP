<?php

namespace Modules\Accounting\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\AccountingPeriodRepositoryInterface;
use Modules\Accounting\Domain\Events\AccountingPeriodLocked;

class LockAccountingPeriodUseCase
{
    public function __construct(
        private AccountingPeriodRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $period = $this->repo->findById($data['id']);

            if (! $period) {
                throw new DomainException('Accounting period not found.');
            }

            if ($period->status === 'locked') {
                throw new DomainException('Accounting period is already locked.');
            }

            if ($period->status !== 'closed') {
                throw new DomainException('Only a closed accounting period can be locked.');
            }

            $lockedBy = $data['locked_by'] ?? null;

            $updated = $this->repo->update($period->id, [
                'status'    => 'locked',
                'locked_by' => $lockedBy,
            ]);

            Event::dispatch(new AccountingPeriodLocked(
                $updated->id,
                $updated->tenant_id,
                $lockedBy,
            ));

            return $updated;
        });
    }
}
