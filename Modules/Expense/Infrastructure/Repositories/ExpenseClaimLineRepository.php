<?php

namespace Modules\Expense\Infrastructure\Repositories;

use Modules\Expense\Domain\Contracts\ExpenseClaimLineRepositoryInterface;
use Modules\Expense\Infrastructure\Models\ExpenseClaimLineModel;

class ExpenseClaimLineRepository implements ExpenseClaimLineRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ExpenseClaimLineModel::find($id);
    }

    public function findByClaimId(string $claimId): iterable
    {
        return ExpenseClaimLineModel::where('claim_id', $claimId)->get();
    }

    public function create(array $data): object
    {
        return ExpenseClaimLineModel::create($data);
    }

    public function deleteByClaimId(string $claimId): void
    {
        ExpenseClaimLineModel::where('claim_id', $claimId)->delete();
    }
}
