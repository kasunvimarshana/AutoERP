<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Entities;
class Activity {
    public function __construct(
        private readonly int    $id,
        private readonly int    $tenantId,
        private ?int            $leadId,
        private ?int            $opportunityId,
        private ?int            $contactId,
        private string          $type,
        private string          $subject,
        private ?string         $description,
        private ?string         $dueDate,
        private ?string         $completedAt,
        private ?int            $assignedTo,
        private ?string         $outcome,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getLeadId(): ?int { return $this->leadId; }
    public function getOpportunityId(): ?int { return $this->opportunityId; }
    public function getContactId(): ?int { return $this->contactId; }
    public function getType(): string { return $this->type; }
    public function getSubject(): string { return $this->subject; }
    public function getDescription(): ?string { return $this->description; }
    public function getDueDate(): ?string { return $this->dueDate; }
    public function getCompletedAt(): ?string { return $this->completedAt; }
    public function getAssignedTo(): ?int { return $this->assignedTo; }
    public function getOutcome(): ?string { return $this->outcome; }
    public function isCompleted(): bool { return $this->completedAt !== null; }
    public function complete(string $outcome = '', ?string $completedAt = null): void {
        if ($this->isCompleted()) throw new \DomainException('Activity is already completed.');
        $this->completedAt = $completedAt ?? (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->outcome = $outcome;
    }
}
