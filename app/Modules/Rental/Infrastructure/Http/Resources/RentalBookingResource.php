<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Rental\Domain\Entities\RentalBooking;

class RentalBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var RentalBooking $booking */
        $booking = $this->resource;

        return [
            'id' => $booking->getId(),
            'tenant_id' => $booking->getTenantId(),
            'org_unit_id' => $booking->getOrgUnitId(),
            'booking_number' => $booking->getBookingNumber(),
            'customer_id' => $booking->getCustomerId(),
            'rental_mode' => $booking->getRentalMode(),
            'ownership_model' => $booking->getOwnershipModel(),
            'status' => $booking->getStatus(),
            'pickup_at' => $booking->getPickupAt(),
            'return_due_at' => $booking->getReturnDueAt(),
            'actual_return_at' => $booking->getActualReturnAt(),
            'pickup_location' => $booking->getPickupLocation(),
            'return_location' => $booking->getReturnLocation(),
            'currency_id' => $booking->getCurrencyId(),
            'rate_plan' => $booking->getRatePlan(),
            'rate_amount' => $booking->getRateAmount(),
            'estimated_amount' => $booking->getEstimatedAmount(),
            'final_amount' => $booking->getFinalAmount(),
            'security_deposit_amount' => $booking->getSecurityDepositAmount(),
            'security_deposit_status' => $booking->getSecurityDepositStatus(),
            'partner_supplier_id' => $booking->getPartnerSupplierId(),
            'notes' => $booking->getNotes(),
            'metadata' => $booking->getMetadata(),
            'row_version' => $booking->getRowVersion(),
            'created_at' => $booking->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $booking->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
