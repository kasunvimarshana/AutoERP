<?php

namespace Modules\Budget\Domain\Contracts;

interface BudgetLineRepositoryInterface
{
    public function findByBudget(string $budgetId): iterable;
    public function findById(string $id): ?object;
    public function create(array $data): object;
    public function addActualAmount(string $id, string $amount): object;
    public function deleteByBudget(string $budgetId): void;
}
