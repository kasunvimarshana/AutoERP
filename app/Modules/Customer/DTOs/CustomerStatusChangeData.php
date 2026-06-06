<?php

declare(strict_types=1);

namespace Modules\Customer\DTOs;

use Modules\Customer\Enums\CustomerStatus;

final readonly class CustomerStatusChangeData
{
    public function __construct(
        public CustomerStatus $newStatus,
        public ?string $reason = null,
        public ?int $changedBy = null,
    ) {}
}
