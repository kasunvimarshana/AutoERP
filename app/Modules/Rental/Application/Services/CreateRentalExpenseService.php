<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\CreateRentalExpenseServiceInterface;
use Modules\Rental\Domain\Entities\RentalExpense;
use Modules\Rental\Domain\RepositoryInterfaces\RentalExpenseRepositoryInterface;

class CreateRentalExpenseService extends BaseService implements CreateRentalExpenseServiceInterface
{
    public function __construct(private readonly RentalExpenseRepositoryInterface $expenseRepository) {}

    protected function handle(array $data): RentalExpense
    {
        $amount = (float) ($data['amount'] ?? 0.0);
        $taxAmount = (float) ($data['tax_amount'] ?? 0.0);
        $totalAmount = isset($data['total_amount']) ? (float) $data['total_amount'] : $amount + $taxAmount;

        $expense = new RentalExpense(
            tenantId: (int) $data['tenant_id'],
            expenseType: (string) ($data['expense_type'] ?? 'other'),
            status: 'draft',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            rentalBookingId: isset($data['rental_booking_id']) ? (int) $data['rental_booking_id'] : null,
            assetId: isset($data['asset_id']) ? (int) $data['asset_id'] : null,
            incurredAt: $data['incurred_at'] ?? null,
            supplierId: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            employeeId: isset($data['employee_id']) ? (int) $data['employee_id'] : null,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            amount: $amount,
            taxAmount: $taxAmount,
            totalAmount: $totalAmount,
            notes: $data['notes'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->expenseRepository->save($expense);
    }
}
