<?php
namespace Modules\CRM\Domain\Entities;
class Opportunity
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $title,
        public readonly ?string $leadId,
        public readonly ?string $contactId,
        public readonly ?string $accountId,
        public readonly string $stage,
        public readonly string $expectedRevenue,
        public readonly float $probability,
        public readonly ?string $assignedTo,
        public readonly ?\DateTimeImmutable $expectedCloseDate,
    ) {}
}
