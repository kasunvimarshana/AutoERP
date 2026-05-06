<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\UpdateRentalBookingServiceInterface;
use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\Exceptions\RentalBookingException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;

class UpdateRentalBookingService extends BaseService implements UpdateRentalBookingServiceInterface
{
    public function __construct(private readonly RentalBookingRepositoryInterface $bookingRepository)
    {
        parent::__construct($bookingRepository);
    }

    protected function handle(array $data): RentalBooking
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->bookingRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw RentalBookingException::notFound($id);
        }

        if (! in_array($existing->getStatus(), ['draft', 'reserved'], true)) {
            throw new \RuntimeException('Only draft or reserved bookings can be updated.');
        }

        $updated = new RentalBooking(
            tenantId: $existing->getTenantId(),
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : $existing->getCustomerId(),
            rentalMode: $data['rental_mode'] ?? $existing->getRentalMode(),
            ownershipModel: $data['ownership_model'] ?? $existing->getOwnershipModel(),
            pickupAt: $data['pickup_at'] ?? $existing->getPickupAt(),
            returnDueAt: $data['return_due_at'] ?? $existing->getReturnDueAt(),
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : $existing->getCurrencyId(),
            ratePlan: $data['rate_plan'] ?? $existing->getRatePlan(),
            rateAmount: isset($data['rate_amount']) ? (float) $data['rate_amount'] : $existing->getRateAmount(),
            status: $existing->getStatus(),
            orgUnitId: $existing->getOrgUnitId(),
            bookingNumber: $existing->getBookingNumber(),
            pickupLocation: $data['pickup_location'] ?? $existing->getPickupLocation(),
            returnLocation: $data['return_location'] ?? $existing->getReturnLocation(),
            estimatedAmount: isset($data['estimated_amount']) ? (float) $data['estimated_amount'] : $existing->getEstimatedAmount(),
            securityDepositAmount: isset($data['security_deposit_amount']) ? (float) $data['security_deposit_amount'] : $existing->getSecurityDepositAmount(),
            partnerSupplierId: array_key_exists('partner_supplier_id', $data) ? (isset($data['partner_supplier_id']) ? (int) $data['partner_supplier_id'] : null) : $existing->getPartnerSupplierId(),
            termsAndConditions: array_key_exists('terms_and_conditions', $data) ? $data['terms_and_conditions'] : $existing->getTermsAndConditions(),
            notes: array_key_exists('notes', $data) ? $data['notes'] : $existing->getNotes(),
            metadata: array_key_exists('metadata', $data) ? $data['metadata'] : $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        return $this->bookingRepository->save($updated);
    }
}
