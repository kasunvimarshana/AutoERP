<?php

declare(strict_types=1);

namespace Modules\Item\DTOs;

final readonly class ItemUsageRuleData
{
    public function __construct(
        public string $moduleCode,
        public bool $isEnabled = true,
    ) {}
}
