<?php declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalAgreementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'reservation_id' => $this->resource->getReservationId(),
            'agreement_number' => $this->resource->getAgreementNumber(),
            'signed_date' => $this->resource->getSignedDate()->format('Y-m-d'),
            'terms_and_conditions' => $this->resource->getTermsAndConditions(),
            'total_price' => $this->resource->getTotalPrice(),
            'deposit_required' => $this->resource->getDepositRequired(),
            'insurance_required' => $this->resource->isInsuranceRequired(),
            'additional_notes' => $this->resource->getAdditionalNotes(),
        ];
    }
}
