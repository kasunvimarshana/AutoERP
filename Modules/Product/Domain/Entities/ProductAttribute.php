<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

/**
 * Represents a single dynamic attribute on a product.
 *
 * Attributes provide an open, extensible mechanism for storing arbitrary
 * product metadata (e.g., colour, weight, material, warranty period) without
 * requiring schema changes.  Each attribute has a machine-readable key,
 * a human-readable display label, a string value, and an optional type hint
 * that consumers may use for rendering or validation.
 */
final class ProductAttribute
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $productId,
        public readonly int $tenantId,
        /** Machine-readable attribute key, e.g. "colour" or "net_weight_kg". */
        public readonly string $attributeKey,
        /** Human-readable label displayed in UIs, e.g. "Colour" or "Net Weight (kg)". */
        public readonly string $attributeLabel,
        /** Stored as a string; interpretation depends on attribute_type. */
        public readonly string $attributeValue,
        /**
         * Optional type hint: text | number | boolean | date | url.
         * Consumers use this for display rendering and client-side validation.
         */
        public readonly string $attributeType,
        /** Zero-based ordering index; lower values appear first. */
        public readonly int $sortOrder,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
