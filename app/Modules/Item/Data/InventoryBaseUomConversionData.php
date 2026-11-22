<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
