<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Rental\Domain\Entities\RentalDeposit;

class RentalDepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var RentalDeposit $deposit */
        $deposit = $this->resource;

        return [
            'id' => $deposit->getId(),
            'tenant_id' => $deposit->getTenantId(),
            'org_unit_id' => $deposit->getOrgUnitId(),
            'rental_booking_id' => $deposit->getRentalBookingId(),
            'currency_id' => $deposit->getCurrencyId(),
            'held_amount' => $deposit->getHeldAmount(),
            'released_amount' => $deposit->getReleasedAmount(),
            'forfeited_amount' => $deposit->getForfeitedAmount(),
            'status' => $deposit->getStatus(),
            'held_at' => $deposit->getHeldAt(),
            'released_at' => $deposit->getReleasedAt(),
            'payment_id' => $deposit->getPaymentId(),
            'journal_entry_id' => $deposit->getJournalEntryId(),
            'metadata' => $deposit->getMetadata(),
            'row_version' => $deposit->getRowVersion(),
            'created_at' => $deposit->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $deposit->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
