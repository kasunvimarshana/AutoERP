<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Entities;

/**
 * A CRM activity — a task, call, meeting, email or note linked to a contact/lead/opportunity.
 * type: call | email | meeting | task | note
 * status: planned | completed | cancelled
 */
class Activity
{
    public const TYPE_CALL    = 'call';
    public const TYPE_EMAIL   = 'email';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_TASK    = 'task';
    public const TYPE_NOTE    = 'note';

    public const STATUS_PLANNED   = 'planned';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $type,
        private string $subject,
        private ?string $description,
        private string $status,
        private ?int $ownerId,
        private ?int $contactId,
        private ?int $leadId,
        private ?int $opportunityId,
        private ?\DateTimeInterface $scheduledAt,
        private ?\DateTimeInterface $completedAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getType(): string { return $this->type; }
    public function getSubject(): string { return $this->subject; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function getOwnerId(): ?int { return $this->ownerId; }
    public function getContactId(): ?int { return $this->contactId; }
    public function getLeadId(): ?int { return $this->leadId; }
    public function getOpportunityId(): ?int { return $this->opportunityId; }
    public function getScheduledAt(): ?\DateTimeInterface { return $this->scheduledAt; }
    public function getCompletedAt(): ?\DateTimeInterface { return $this->completedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function complete(?string $description = null): void
    {
        if ($this->status !== self::STATUS_PLANNED) {
            throw new \DomainException("Only planned activities can be completed.");
        }
        $this->status      = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
        if ($description !== null) {
            $this->description = $description;
        }
    }

    public function cancel(): void
    {
        if ($this->status === self::STATUS_COMPLETED) {
            throw new \DomainException("Completed activities cannot be cancelled.");
        }
        $this->status = self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
}
