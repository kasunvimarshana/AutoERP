<?php

namespace Modules\Accounting\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\AccountingPeriodRepositoryInterface;
use Modules\Accounting\Domain\Events\AccountingPeriodClosed;

class CloseAccountingPeriodUseCase
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

            if ($period->status === 'closed') {
                throw new DomainException('Accounting period is already closed.');
            }

            if ($period->status === 'locked') {
                throw new DomainException('Cannot close a locked accounting period.');
            }

            $closedBy = $data['closed_by'] ?? null;

            $updated = $this->repo->update($period->id, [
                'status'    => 'closed',
                'closed_by' => $closedBy,
                'closed_at' => now()->toDateTimeString(),
            ]);

            Event::dispatch(new AccountingPeriodClosed(
                $updated->id,
                $updated->tenant_id,
                $closedBy,
            ));

            return $updated;
        });
    }
}
