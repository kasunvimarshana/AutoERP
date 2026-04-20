<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class UomConversionData
{
    public function __construct(
        public readonly int $from_uom_id,
        public readonly int $to_uom_id,
        public readonly string $factor,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            from_uom_id: (int) $data['from_uom_id'],
            to_uom_id: (int) $data['to_uom_id'],
            factor: (string) $data['factor'],
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
