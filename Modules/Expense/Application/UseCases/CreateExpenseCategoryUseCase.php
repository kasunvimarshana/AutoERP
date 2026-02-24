<?php

namespace Modules\Expense\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Expense\Domain\Contracts\ExpenseCategoryRepositoryInterface;

class CreateExpenseCategoryUseCase
{
    public function __construct(
        private ExpenseCategoryRepositoryInterface $categoryRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            return $this->categoryRepo->create([
                'tenant_id'   => $data['tenant_id'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active'   => true,
            ]);
        });
    }
}
