<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\CreateRentalSettlementServiceInterface;
use Modules\Rental\Domain\Entities\RentalSettlement;
use Modules\Rental\Domain\RepositoryInterfaces\RentalSettlementRepositoryInterface;

class CreateRentalSettlementService extends BaseService implements CreateRentalSettlementServiceInterface
{
    public function __construct(private readonly RentalSettlementRepositoryInterface $settlementRepository) {}

    protected function handle(array $data): RentalSettlement
    {
        $amount = (float) ($data['amount'] ?? 0.0);
        $taxAmount = (float) ($data['tax_amount'] ?? 0.0);
        $totalAmount = isset($data['total_amount']) ? (float) $data['total_amount'] : $amount + $taxAmount;

        $settlement = new RentalSettlement(
            tenantId: (int) $data['tenant_id'],
            rentalBookingId: (int) $data['rental_booking_id'],
            settlementPartyType: (string) ($data['settlement_party_type'] ?? 'driver'),
            settlementType: (string) ($data['settlement_type'] ?? 'commission'),
            status: 'draft',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            employeeId: isset($data['employee_id']) ? (int) $data['employee_id'] : null,
            supplierId: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            amount: $amount,
            taxAmount: $taxAmount,
            totalAmount: $totalAmount,
            notes: $data['notes'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->settlementRepository->save($settlement);
    }
}
