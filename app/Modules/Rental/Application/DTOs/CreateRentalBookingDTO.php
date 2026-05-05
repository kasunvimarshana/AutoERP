<?php

declare(strict_types=1);

namespace Modules\Rental\Application\DTOs;

class CreateRentalBookingDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $customerId,
        public readonly string $bookingNumber,
        public readonly string $bookingType,
        public readonly string $fleetSource,
        public readonly ?int $orgUnitId = null,
        public readonly ?string $scheduledStartAt = null,
        public readonly ?string $scheduledEndAt = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
    ) {}
}
