<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

/**
 * A single value option within a VariationTemplate (e.g. "Red" in "Color").
 */
class VariationValue
{
    public function __construct(
        private readonly int    $id,
        private readonly int    $variationTemplateId,
        private readonly string $value,
    ) {}

    public function getId(): int                  { return $this->id; }
    public function getVariationTemplateId(): int { return $this->variationTemplateId; }
    public function getValue(): string            { return $this->value; }
}
