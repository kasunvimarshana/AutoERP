<?php

namespace Modules\Expense\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Expense\Domain\Contracts\ExpenseClaimRepositoryInterface;
use Modules\Expense\Domain\Events\ExpenseClaimApproved;

class ApproveExpenseClaimUseCase
{
    public function __construct(
        private ExpenseClaimRepositoryInterface $claimRepo,
    ) {}

    public function execute(string $claimId, string $approverId): object
    {
        return DB::transaction(function () use ($claimId, $approverId) {
            $claim = $this->claimRepo->findById($claimId);

            if (! $claim) {
                throw new DomainException('Expense claim not found.');
            }

            if ($claim->status !== 'submitted') {
                throw new DomainException('Only submitted expense claims can be approved.');
            }

            $updated = $this->claimRepo->update($claimId, [
                'status'      => 'approved',
                'approver_id' => $approverId,
                'approved_at' => now(),
            ]);

            Event::dispatch(new ExpenseClaimApproved($claimId, $claim->tenant_id, $approverId));

            return $updated;
        });
    }
}
