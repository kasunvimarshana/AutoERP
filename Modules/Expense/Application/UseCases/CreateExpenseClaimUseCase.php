<?php

namespace Modules\Expense\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Expense\Domain\Contracts\ExpenseClaimLineRepositoryInterface;
use Modules\Expense\Domain\Contracts\ExpenseClaimRepositoryInterface;

class CreateExpenseClaimUseCase
{
    public function __construct(
        private ExpenseClaimRepositoryInterface     $claimRepo,
        private ExpenseClaimLineRepositoryInterface $lineRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'];
            $lines    = $data['lines'] ?? [];

            // Calculate total using BCMath — no floating-point arithmetic
            $total = '0.00000000';
            foreach ($lines as $line) {
                $amount = (string) ($line['amount'] ?? '0');
                $total  = bcadd($total, $amount, 8);
            }

            $claim = $this->claimRepo->create([
                'tenant_id'   => $tenantId,
                'employee_id' => $data['employee_id'],
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'currency'    => $data['currency'] ?? 'USD',
                'total_amount' => $total,
                'status'      => 'draft',
            ]);

            foreach ($lines as $line) {
                $this->lineRepo->create([
                    'tenant_id'           => $tenantId,
                    'claim_id'            => $claim->id,
                    'expense_category_id' => $line['expense_category_id'] ?? null,
                    'description'         => $line['description'],
                    'expense_date'        => $line['expense_date'],
                    'amount'              => $line['amount'],
                    'receipt_path'        => $line['receipt_path'] ?? null,
                ]);
            }

            return $claim;
        });
    }
}
