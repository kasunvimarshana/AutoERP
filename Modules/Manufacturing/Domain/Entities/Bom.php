<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Domain\Entities;

/**
 * Bill of Materials: defines the components needed to manufacture one finished product.
 */
class Bom
{
    /** @param BomLine[] $lines */
    public function __construct(
        private readonly int     $id,
        private readonly int     $tenantId,
        private readonly int     $productId,
        private readonly ?int    $variantId,
        private readonly string  $outputQuantity,
        private readonly ?string $reference,
        private readonly bool    $isActive,
        private readonly array   $lines,
    ) {}

    public function getId(): int                { return $this->id; }
    public function getTenantId(): int          { return $this->tenantId; }
    public function getProductId(): int         { return $this->productId; }
    public function getVariantId(): ?int        { return $this->variantId; }
    public function getOutputQuantity(): string { return $this->outputQuantity; }
    public function getReference(): ?string     { return $this->reference; }
    public function isActive(): bool            { return $this->isActive; }

    /** @return BomLine[] */
    public function getLines(): array { return $this->lines; }

    /**
     * Returns the total component quantity scaled for a given production quantity.
     * Uses BCMath for precision.
     */
    public function scaledComponents(string $productionQty): array
    {
        $scale = bcdiv($productionQty, $this->outputQuantity, 10);

        return array_map(
            fn (BomLine $line) => [
                'component_product_id' => $line->getComponentProductId(),
                'component_variant_id' => $line->getComponentVariantId(),
                'required_quantity'    => bcmul($line->getQuantity(), $scale, 4),
            ],
            $this->lines
        );
    }
}
