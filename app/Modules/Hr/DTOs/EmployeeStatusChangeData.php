<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\DTOs;

use Modules\Hr\Enums\EmployeeStatus;

final readonly class EmployeeStatusChangeData
{
    public function __construct(
        public EmployeeStatus $newStatus,
        public int $rowVersion,
        public ?string $reason = null,
        public ?int $changedBy = null,
    ) {}
}
