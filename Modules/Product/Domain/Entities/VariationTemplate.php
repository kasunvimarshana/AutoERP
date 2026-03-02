<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

/**
 * Variation Template: defines a named set of variation values (e.g. "Color": Red, Blue, Green).
 * Based on VariationTemplate / VariationValueTemplate in the PHP_POS reference.
 */
class VariationTemplate
{
    /** @param VariationValue[] $values */
    public function __construct(
        private readonly int    $id,
        private readonly int    $tenantId,
        private readonly string $name,
        private readonly array  $values,
    ) {}

    public function getId(): int        { return $this->id; }
    public function getTenantId(): int  { return $this->tenantId; }
    public function getName(): string   { return $this->name; }

    /** @return VariationValue[] */
    public function getValues(): array  { return $this->values; }
}
