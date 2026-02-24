<?php

namespace Modules\Expense\Domain\Contracts;

interface ExpenseClaimLineRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByClaimId(string $claimId): iterable;
    public function create(array $data): object;
    public function deleteByClaimId(string $claimId): void;
}
