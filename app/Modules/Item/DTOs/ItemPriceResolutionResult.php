<?php

declare(strict_types=1);

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
