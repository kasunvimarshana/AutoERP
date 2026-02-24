<?php
namespace Modules\CRM\Domain\Entities;
class Activity
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $type,
        public readonly string $subject,
        public readonly ?string $description,
        public readonly string $status,
        public readonly ?string $assignedTo,
        public readonly ?string $relatedType,
        public readonly ?string $relatedId,
        public readonly ?\DateTimeImmutable $dueAt,
        public readonly ?\DateTimeImmutable $completedAt,
    ) {}
}
