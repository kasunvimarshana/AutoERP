<?php

declare(strict_types=1);

namespace Modules\Item\DTOs;

use Modules\Item\Enums\ItemCodeType;

final readonly class ItemCodeData
{
    public function __construct(
        public ItemCodeType $codeType,
        public string $code,
        public ?int $itemVariantId = null,
        public ?string $partyType = null,
        public ?int $partyId = null,
        public bool $isPrimary = false,
    ) {}
}
