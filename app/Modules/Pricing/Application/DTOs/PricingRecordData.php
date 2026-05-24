<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

final readonly class PricingRecordData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public int|string $tenantId,
        public array $attributes,
        public ?int $rowVersion = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int|string $tenantId, array $data): self
    {
        return new self(
            tenantId: $tenantId,
            attributes: $data,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : null,
        );
    }
}
