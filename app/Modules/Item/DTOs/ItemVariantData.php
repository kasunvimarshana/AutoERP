<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Item\DTOs;

final readonly class ItemVariantData
{
    /**
     * @param  array<string, mixed>|null  $attributes
     */
    public function __construct(
        public string $code,
        public string $name,
        public ?string $sku = null,
        public ?string $barcode = null,
        public ?array $attributes = null,
        public bool $isActive = true,
    ) {}
}
