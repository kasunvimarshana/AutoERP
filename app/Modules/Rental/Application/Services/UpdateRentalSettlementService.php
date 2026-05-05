<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\UpdateRentalSettlementServiceInterface;
use Modules\Rental\Domain\Entities\RentalSettlement;
use Modules\Rental\Domain\RepositoryInterfaces\RentalSettlementRepositoryInterface;

class UpdateRentalSettlementService extends BaseService implements UpdateRentalSettlementServiceInterface
{
    public function __construct(private readonly RentalSettlementRepositoryInterface $settlementRepository) {}

    protected function handle(array $data): RentalSettlement
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->settlementRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new \RuntimeException("Rental settlement {$id} not found.");
        }

        if (in_array($existing->getStatus(), ['paid', 'voided'], true)) {
            throw new \RuntimeException("Cannot update a {$existing->getStatus()} settlement.");
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : $existing->getAmount();
        $taxAmount = isset($data['tax_amount']) ? (float) $data['tax_amount'] : $existing->getTaxAmount();
        $totalAmount = isset($data['total_amount']) ? (float) $data['total_amount'] : $amount + $taxAmount;

        $updated = new RentalSettlement(
            tenantId: $tenantId,
            rentalBookingId: $existing->getRentalBookingId(),
            settlementPartyType: $data['settlement_party_type'] ?? $existing->getSettlementPartyType(),
            settlementType: $data['settlement_type'] ?? $existing->getSettlementType(),
            status: $data['status'] ?? $existing->getStatus(),
            orgUnitId: $data['org_unit_id'] ?? $existing->getOrgUnitId(),
            employeeId: $data['employee_id'] ?? $existing->getEmployeeId(),
            supplierId: $data['supplier_id'] ?? $existing->getSupplierId(),
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

        return $this->settlementRepository->save($updated);
    }
}
