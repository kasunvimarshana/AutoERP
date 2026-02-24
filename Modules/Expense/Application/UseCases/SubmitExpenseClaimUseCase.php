<?php

namespace Modules\Expense\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Expense\Domain\Contracts\ExpenseClaimRepositoryInterface;
use Modules\Expense\Domain\Events\ExpenseClaimSubmitted;

class SubmitExpenseClaimUseCase
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

            if ($claim->status !== 'draft') {
                throw new DomainException('Only draft expense claims can be submitted.');
            }

            $updated = $this->claimRepo->update($claimId, [
                'status'       => 'submitted',
                'submitted_at' => now(),
            ]);

            Event::dispatch(new ExpenseClaimSubmitted($claimId, $claim->tenant_id));

            return $updated;
        });
    }
}
