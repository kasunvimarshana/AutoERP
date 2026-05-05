<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\CreateRentalChargeServiceInterface;
use Modules\Rental\Domain\Entities\RentalCharge;
use Modules\Rental\Domain\RepositoryInterfaces\RentalChargeRepositoryInterface;

class CreateRentalChargeService extends BaseService implements CreateRentalChargeServiceInterface
{
    public function __construct(private readonly RentalChargeRepositoryInterface $chargeRepository) {}

    protected function handle(array $data): RentalCharge
    {
        $amount = (float) ($data['amount'] ?? 0.0);
        $taxAmount = (float) ($data['tax_amount'] ?? 0.0);
        $totalAmount = isset($data['total_amount']) ? (float) $data['total_amount'] : $amount + $taxAmount;

        $charge = new RentalCharge(
            tenantId: (int) $data['tenant_id'],
            rentalBookingId: (int) $data['rental_booking_id'],
            chargeType: (string) ($data['charge_type'] ?? 'rental_fee'),
            chargeDirection: (string) ($data['charge_direction'] ?? 'receivable'),
            status: 'draft',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            rentalIncidentId: isset($data['rental_incident_id']) ? (int) $data['rental_incident_id'] : null,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            amount: $amount,
            taxAmount: $taxAmount,
            totalAmount: $totalAmount,
            dueDate: $data['due_date'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->chargeRepository->save($charge);
    }
}
