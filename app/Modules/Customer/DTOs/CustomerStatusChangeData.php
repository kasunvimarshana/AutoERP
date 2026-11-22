<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
