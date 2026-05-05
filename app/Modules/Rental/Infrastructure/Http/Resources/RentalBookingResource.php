<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'org_unit_id' => $this->getOrgUnitId(),
            'booking_number' => $this->getBookingNumber(),
            'customer_id' => $this->getCustomerId(),
            'booking_type' => $this->getBookingType(),
            'fleet_source' => $this->getFleetSource(),
            'status' => $this->getStatus(),
            'scheduled_start_at' => $this->getScheduledStartAt()?->format('c'),
            'scheduled_end_at' => $this->getScheduledEndAt()?->format('c'),
            'actual_start_at' => $this->getActualStartAt()?->format('c'),
            'actual_end_at' => $this->getActualEndAt()?->format('c'),
            'subtotal' => $this->getSubtotal(),
            'discount_amount' => $this->getDiscountAmount(),
            'tax_amount' => $this->getTaxAmount(),
            'deposit_amount' => $this->getDepositAmount(),
            'total_amount' => $this->getTotalAmount(),
            'deposit_status' => $this->getDepositStatus(),
            'ar_transaction_id' => $this->getArTransactionId(),
            'journal_entry_id' => $this->getJournalEntryId(),
            'notes' => $this->getNotes(),
            'metadata' => $this->getMetadata(),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
