<?php

namespace Modules\Expense\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Expense\Domain\Contracts\ExpenseClaimRepositoryInterface;
use Modules\Expense\Domain\Events\ExpenseClaimReimbursed;

class ReimburseExpenseClaimUseCase
{
    public function __construct(
        private ExpenseClaimRepositoryInterface $claimRepo,
    ) {}

    public function execute(string $claimId): object
    {
        return DB::transaction(function () use ($claimId) {
            $claim = $this->claimRepo->findById($claimId);

            if (! $claim) {
                throw new DomainException('Expense claim not found.');
            }

            if ($claim->status !== 'approved') {
                throw new DomainException('Only approved expense claims can be reimbursed.');
            }

            $updated = $this->claimRepo->update($claimId, [
                'status'         => 'reimbursed',
                'reimbursed_at'  => now(),
            ]);

            Event::dispatch(new ExpenseClaimReimbursed(
                claimId:     $claimId,
                tenantId:    $claim->tenant_id,
                employeeId:  (string) ($claim->employee_id ?? ''),
                totalAmount: (string) ($claim->total_amount ?? '0'),
                currency:    (string) ($claim->currency ?? 'USD'),
            ));

            return $updated;
        });
    }
}
