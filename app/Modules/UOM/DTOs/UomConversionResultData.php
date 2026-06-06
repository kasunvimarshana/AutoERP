<?php

declare(strict_types=1);

namespace Modules\UOM\DTOs;

final readonly class UomConversionResultData
{
    public function __construct(
        public string $quantity,
        public array $fromUom,
        public array $toUom,
        public string $conversionFactor,
        public string $convertedQuantity,
    ) {}

    public function toArray(): array
    {
        return [
            'quantity' => $this->quantity,
            'from_uom' => $this->fromUom,
            'to_uom' => $this->toUom,
            'conversion_factor' => $this->conversionFactor,
            'converted_quantity' => $this->convertedQuantity,
        ];
    }
}
