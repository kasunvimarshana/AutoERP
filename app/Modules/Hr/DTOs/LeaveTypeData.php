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

final readonly class LeaveTypeData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $color = '#10B981',
        public bool $isPaid = true,
        public bool $requiresAttachment = false,
        public int $attachmentMandatoryAfterDays = 2,
        public bool $allowHalfDay = true,
        public bool $allowHourly = false,
        public bool $isCompensatory = false,
        public bool $sandwichRuleApplied = false,
        public string $status = 'active',
        public ?int $organizationUnitId = null,
    ) {}
}
