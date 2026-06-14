<?php

declare(strict_types=1);

namespace Modules\VehicleRental\DTOs;

final readonly class RentalInspectionData
{
    /**
     * @param  list<string>|null  $attachments
     */
    public function __construct(
        public string $inspectedAt,
        public string $odometer,
        public ?string $fuelLevel = null,
        public ?string $conditionNotes = null,
        public ?string $damageNotes = null,
        public ?array $attachments = null,
        public ?int $inspectedBy = null,
        public string $damageAmount = '0.000000',
        public bool $isDamageBillable = false,
    ) {}
}
