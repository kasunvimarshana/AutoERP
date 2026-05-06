<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Asset\Application\Contracts\FindAssetAvailabilityServiceInterface;
use Modules\Asset\Application\Contracts\SyncAssetAvailabilityServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\CreateRentalBookingServiceInterface;
use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\Exceptions\RentalBookingException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;

class CreateRentalBookingService extends BaseService implements CreateRentalBookingServiceInterface
{
    public function __construct(
        private readonly RentalBookingRepositoryInterface $bookingRepository,
        private readonly FindAssetAvailabilityServiceInterface $findAvailabilityService,
        private readonly SyncAssetAvailabilityServiceInterface $syncAvailabilityService,
    ) {
        parent::__construct($bookingRepository);
    }

    protected function handle(array $data): RentalBooking
    {
        $tenantId = (int) $data['tenant_id'];
        $orgUnitId = isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null;
        $assetIds = array_map('intval', (array) ($data['asset_ids'] ?? []));
        $pickupAt = (string) $data['pickup_at'];
        $returnDueAt = (string) $data['return_due_at'];
        $changedBy = isset($data['changed_by']) ? (int) $data['changed_by'] : null;

        foreach ($assetIds as $assetId) {
            $this->assertAssetAvailableForRental($tenantId, $assetId);
            $this->assertNoConflictingBookings($tenantId, $assetId, $pickupAt, $returnDueAt);
        }

        $bookingNumber = $this->bookingRepository->nextBookingNumber($tenantId, $orgUnitId);

        $booking = new RentalBooking(
            tenantId: $tenantId,
            customerId: (int) $data['customer_id'],
            rentalMode: (string) $data['rental_mode'],
            ownershipModel: (string) ($data['ownership_model'] ?? 'owned_fleet'),
            pickupAt: $pickupAt,
            returnDueAt: $returnDueAt,
            currencyId: (int) $data['currency_id'],
            ratePlan: (string) $data['rate_plan'],
            rateAmount: (float) $data['rate_amount'],
            status: 'reserved',
            orgUnitId: $orgUnitId,
            bookingNumber: $bookingNumber,
            pickupLocation: $data['pickup_location'] ?? null,
            returnLocation: $data['return_location'] ?? null,
            estimatedAmount: (float) ($data['estimated_amount'] ?? 0.0),
            securityDepositAmount: (float) ($data['security_deposit_amount'] ?? 0.0),
            partnerSupplierId: isset($data['partner_supplier_id']) ? (int) $data['partner_supplier_id'] : null,
            termsAndConditions: $data['terms_and_conditions'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        $saved = $this->bookingRepository->save($booking);

        foreach ($assetIds as $assetId) {
            $this->syncAvailabilityService->execute([
                'tenant_id' => $tenantId,
                'org_unit_id' => $orgUnitId,
                'asset_id' => $assetId,
                'target_status' => 'reserved',
                'reason_code' => 'rental_reserved',
                'source_type' => 'rental_booking',
                'source_id' => $saved->getId(),
                'changed_by' => $changedBy,
            ]);
        }

        return $saved;
    }

    private function assertAssetAvailableForRental(int $tenantId, int $assetId): void
    {
        $state = $this->findAvailabilityService->findCurrentState($tenantId, $assetId);

        if ($state === null) {
            return; // No state recorded — treat as available
        }

        $blockedStatuses = ['in_service', 'blocked', 'internal_use', 'rented', 'reserved'];

        if (in_array($state->getAvailabilityStatus(), $blockedStatuses, true)) {
            throw RentalBookingException::assetNotAvailable($assetId, $state->getAvailabilityStatus());
        }
    }

    private function assertNoConflictingBookings(
        int $tenantId,
        int $assetId,
        string $pickupAt,
        string $returnDueAt,
    ): void {
        $conflicts = $this->bookingRepository->findConflictingBookings($tenantId, $assetId, $pickupAt, $returnDueAt);

        if (count($conflicts) > 0) {
            throw RentalBookingException::conflictingBookingExists($assetId);
        }
    }
}
