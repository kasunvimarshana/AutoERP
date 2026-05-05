<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\UpdateRentalChargeServiceInterface;
use Modules\Rental\Domain\Entities\RentalCharge;
use Modules\Rental\Domain\RepositoryInterfaces\RentalChargeRepositoryInterface;

class UpdateRentalChargeService extends BaseService implements UpdateRentalChargeServiceInterface
{
    public function __construct(private readonly RentalChargeRepositoryInterface $chargeRepository) {}

    protected function handle(array $data): RentalCharge
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->chargeRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new \RuntimeException("Rental charge {$id} not found.");
        }

        if (in_array($existing->getStatus(), ['paid', 'voided'], true)) {
            throw new \RuntimeException("Cannot update a {$existing->getStatus()} charge.");
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : $existing->getAmount();
        $taxAmount = isset($data['tax_amount']) ? (float) $data['tax_amount'] : $existing->getTaxAmount();
        $totalAmount = isset($data['total_amount']) ? (float) $data['total_amount'] : $amount + $taxAmount;

        $updated = new RentalCharge(
            tenantId: $tenantId,
            rentalBookingId: $existing->getRentalBookingId(),
            chargeType: $data['charge_type'] ?? $existing->getChargeType(),
            chargeDirection: $data['charge_direction'] ?? $existing->getChargeDirection(),
            status: $data['status'] ?? $existing->getStatus(),
            orgUnitId: $data['org_unit_id'] ?? $existing->getOrgUnitId(),
            rentalIncidentId: $data['rental_incident_id'] ?? $existing->getRentalIncidentId(),
            currencyId: $data['currency_id'] ?? $existing->getCurrencyId(),
            amount: $amount,
            taxAmount: $taxAmount,
            totalAmount: $totalAmount,
            dueDate: $data['due_date'] ?? $existing->getDueDate(),
            journalEntryId: $data['journal_entry_id'] ?? $existing->getJournalEntryId(),
            paymentId: $data['payment_id'] ?? $existing->getPaymentId(),
            reversalOfId: $data['reversal_of_id'] ?? $existing->getReversalOfId(),
            metadata: $data['metadata'] ?? $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $id,
        );

        return $this->chargeRepository->save($updated);
    }
}
