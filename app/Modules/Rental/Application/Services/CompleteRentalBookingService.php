<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Asset\Application\Contracts\SyncAssetAvailabilityServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\CompleteRentalBookingServiceInterface;
use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\Exceptions\RentalBookingException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;

class CompleteRentalBookingService extends BaseService implements CompleteRentalBookingServiceInterface
{
    public function __construct(
        private readonly RentalBookingRepositoryInterface $bookingRepository,
        private readonly SyncAssetAvailabilityServiceInterface $syncAvailabilityService,
    ) {
        parent::__construct($bookingRepository);
    }

    protected function handle(array $data): RentalBooking
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];
        $assetIds = array_map('intval', (array) ($data['asset_ids'] ?? []));
        $changedBy = isset($data['changed_by']) ? (int) $data['changed_by'] : null;

        $existing = $this->bookingRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw RentalBookingException::notFound($id);
        }

        if (! $existing->isTransitionAllowed('completed')) {
            throw RentalBookingException::invalidTransition($existing->getStatus(), 'completed');
        }

        $completed = new RentalBooking(
            tenantId: $existing->getTenantId(),
            customerId: $existing->getCustomerId(),
            rentalMode: $existing->getRentalMode(),
            ownershipModel: $existing->getOwnershipModel(),
            pickupAt: $existing->getPickupAt(),
            returnDueAt: $existing->getReturnDueAt(),
            currencyId: $existing->getCurrencyId(),
            ratePlan: $existing->getRatePlan(),
            rateAmount: $existing->getRateAmount(),
            status: 'completed',
            orgUnitId: $existing->getOrgUnitId(),
            bookingNumber: $existing->getBookingNumber(),
            actualReturnAt: $data['actual_return_at'] ?? $existing->getActualReturnAt(),
            pickupLocation: $existing->getPickupLocation(),
            returnLocation: $existing->getReturnLocation(),
            estimatedAmount: $existing->getEstimatedAmount(),
            finalAmount: isset($data['final_amount']) ? (float) $data['final_amount'] : $existing->getFinalAmount(),
            securityDepositAmount: $existing->getSecurityDepositAmount(),
            securityDepositStatus: $data['security_deposit_status'] ?? $existing->getSecurityDepositStatus(),
            partnerSupplierId: $existing->getPartnerSupplierId(),
            termsAndConditions: $existing->getTermsAndConditions(),
            notes: array_key_exists('notes', $data) ? $data['notes'] : $existing->getNotes(),
            metadata: $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        $saved = $this->bookingRepository->save($completed);

        foreach ($assetIds as $assetId) {
            $this->syncAvailabilityService->execute([
                'tenant_id' => $tenantId,
                'org_unit_id' => $existing->getOrgUnitId(),
                'asset_id' => $assetId,
                'target_status' => 'available',
                'reason_code' => 'rental_completed',
                'source_type' => 'rental_booking',
                'source_id' => $id,
                'changed_by' => $changedBy,
            ]);
        }

        return $saved;
    }
}
