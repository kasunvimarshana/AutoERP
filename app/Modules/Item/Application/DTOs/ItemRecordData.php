<?php

declare(strict_types=1);

namespace Modules\Item\Application\DTOs;

final readonly class ItemRecordData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public int $tenantId,
        public array $attributes,
        public ?int $rowVersion,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(int|string $tenantId, array $attributes): self
    {
        return new self(
            tenantId: (int) $tenantId,
            attributes: $attributes,
            rowVersion: isset($attributes['row_version']) ? (int) $attributes['row_version'] : null,
        );
    }
}
