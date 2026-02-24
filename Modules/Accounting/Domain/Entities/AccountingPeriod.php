<?php

namespace Modules\Accounting\Domain\Entities;

use Modules\Accounting\Domain\Enums\AccountingPeriodStatus;

class AccountingPeriod
{
    public function __construct(
        private readonly string                $id,
        private readonly string                $tenantId,
        private readonly string                $name,
        private readonly string                $startDate,
        private readonly string                $endDate,
        private readonly AccountingPeriodStatus $status,
        private readonly ?string               $closedBy,
        private readonly ?string               $closedAt,
        private readonly ?string               $lockedBy = null,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getStartDate(): string { return $this->startDate; }
    public function getEndDate(): string { return $this->endDate; }
    public function getStatus(): AccountingPeriodStatus { return $this->status; }
    public function getClosedBy(): ?string { return $this->closedBy; }
    public function getClosedAt(): ?string { return $this->closedAt; }
    public function getLockedBy(): ?string { return $this->lockedBy; }
}
