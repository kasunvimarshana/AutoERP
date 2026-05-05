<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\UpdateRentalExpenseServiceInterface;
use Modules\Rental\Domain\Entities\RentalExpense;
use Modules\Rental\Domain\RepositoryInterfaces\RentalExpenseRepositoryInterface;

class UpdateRentalExpenseService extends BaseService implements UpdateRentalExpenseServiceInterface
{
    public function __construct(private readonly RentalExpenseRepositoryInterface $expenseRepository) {}

    protected function handle(array $data): RentalExpense
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->expenseRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new \RuntimeException("Rental expense {$id} not found.");
        }

        if (in_array($existing->getStatus(), ['reimbursed', 'voided'], true)) {
            throw new \RuntimeException("Cannot update a {$existing->getStatus()} expense.");
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : $existing->getAmount();
        $taxAmount = isset($data['tax_amount']) ? (float) $data['tax_amount'] : $existing->getTaxAmount();
        $totalAmount = isset($data['total_amount']) ? (float) $data['total_amount'] : $amount + $taxAmount;

        $updated = new RentalExpense(
            tenantId: $tenantId,
            expenseType: $data['expense_type'] ?? $existing->getExpenseType(),
            status: $data['status'] ?? $existing->getStatus(),
            orgUnitId: $data['org_unit_id'] ?? $existing->getOrgUnitId(),
            rentalBookingId: $data['rental_booking_id'] ?? $existing->getRentalBookingId(),
            assetId: $data['asset_id'] ?? $existing->getAssetId(),
            incurredAt: $data['incurred_at'] ?? $existing->getIncurredAt(),
            supplierId: $data['supplier_id'] ?? $existing->getSupplierId(),
            employeeId: $data['employee_id'] ?? $existing->getEmployeeId(),
            currencyId: $data['currency_id'] ?? $existing->getCurrencyId(),
            amount: $amount,
            taxAmount: $taxAmount,
            totalAmount: $totalAmount,
            journalEntryId: $data['journal_entry_id'] ?? $existing->getJournalEntryId(),
            paymentId: $data['payment_id'] ?? $existing->getPaymentId(),
            reversalOfId: $data['reversal_of_id'] ?? $existing->getReversalOfId(),
            notes: $data['notes'] ?? $existing->getNotes(),
            metadata: $data['metadata'] ?? $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $id,
        );

        return $this->expenseRepository->save($updated);
    }
}
