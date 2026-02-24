<?php

namespace Modules\Accounting\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\AccountingPeriodRepositoryInterface;
use Modules\Accounting\Domain\Events\AccountingPeriodCreated;

class CreateAccountingPeriodUseCase
{
    public function __construct(
        private AccountingPeriodRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            if (empty(trim($data['name'] ?? ''))) {
                throw new DomainException('Period name is required.');
            }

            $startDate = $data['start_date'] ?? '';
            $endDate   = $data['end_date']   ?? '';

            if (empty($startDate) || empty($endDate)) {
                throw new DomainException('Start date and end date are required.');
            }

            if ($startDate >= $endDate) {
                throw new DomainException('End date must be after start date.');
            }

            $tenantId = $data['tenant_id'];

            if ($this->repo->hasOverlap($tenantId, $startDate, $endDate)) {
                throw new DomainException(
                    'The period overlaps with an existing accounting period for this tenant.'
                );
            }

            $period = $this->repo->create([
                'tenant_id'  => $tenantId,
                'name'       => trim($data['name']),
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'status'     => 'draft',
            ]);

            Event::dispatch(new AccountingPeriodCreated(
                $period->id,
                $period->tenant_id,
                $period->name,
                $period->start_date,
                $period->end_date,
            ));

            return $period;
        });
    }
}
