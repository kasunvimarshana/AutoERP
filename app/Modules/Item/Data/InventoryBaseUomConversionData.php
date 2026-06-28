<?php

declare(strict_types=1);

namespace Modules\Item\Data;

final readonly class InventoryBaseUomConversionData
{
    /**
     * @param list<array<string, string>> $previewRows
     */
    public function __construct(
        public array $previewRows,
    ) {}
}
