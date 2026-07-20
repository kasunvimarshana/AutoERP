<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

use Modules\VehicleRental\Constants\VehicleRentalFinancialDocument;

final readonly class RentalFinancialDocumentData
{
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $invoiceDate,
        public int $expectedVersion,
        public ?int $actorId,
        public string $exchangeRate = VehicleRentalFinancialDocument::DEFAULT_EXCHANGE_RATE,
        public ?string $notes = null,
    ) {}
}
