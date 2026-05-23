<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

final readonly class FinanceRecordData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public int $tenantId,
        public array $attributes,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(int|string $tenantId, array $attributes): self
    {
        return new self((int) $tenantId, $attributes);
    }
}
