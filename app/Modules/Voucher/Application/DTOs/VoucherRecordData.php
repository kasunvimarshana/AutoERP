<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\DTOs;

class VoucherRecordData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public readonly int|string $tenantId,
        public readonly array $attributes,
        public readonly ?int $rowVersion = null,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(int|string $tenantId, array $attributes): self
    {
        $rowVersion = isset($attributes['row_version']) ? (int) $attributes['row_version'] : null;
        unset($attributes['row_version']);

        return new self($tenantId, $attributes, $rowVersion);
    }
}
