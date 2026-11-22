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

final readonly class ItemPriceResolutionResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?string $amount,
        public ?int $currencyId,
        public ?int $uomId,
        public string $source,
        public ?string $priceType = null,
        public ?int $priceId = null,
        public array $metadata = [],
    ) {}

    public function hasAmount(): bool
    {
        return $this->amount !== null;
    }
}
