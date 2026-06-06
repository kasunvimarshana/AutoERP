<?php

declare(strict_types=1);

namespace Modules\Vehicle\DTOs;

use Modules\Vehicle\Enums\VehicleAttributeDataType;

final readonly class VehicleAttributeData
{
    public function __construct(
        public string $attributeKey,
        public ?string $attributeValue = null,
        public VehicleAttributeDataType $dataType = VehicleAttributeDataType::Text,
        public int $sortOrder = 0,
    ) {}
}
