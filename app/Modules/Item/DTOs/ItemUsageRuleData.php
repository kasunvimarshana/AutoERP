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

final readonly class ItemUsageRuleData
{
    public function __construct(
        public string $moduleCode,
        public bool $isEnabled = true,
    ) {}
}
