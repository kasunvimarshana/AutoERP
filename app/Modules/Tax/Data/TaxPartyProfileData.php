<?php

declare(strict_types=1);

namespace Modules\Tax\Data;

final readonly class TaxPartyProfileData
{
    public function __construct(
        public int $profileId,
        public ?int $taxGroupId,
        public ?string $taxGroupCode,
        public ?string $taxGroupName,
        public string $exemptionStatus,
        public ?string $registrationNumber,
    ) {}
}
