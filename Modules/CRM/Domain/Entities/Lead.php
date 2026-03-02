<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Entities;
use Modules\CRM\Domain\Enums\LeadStatus;
class Lead {
    public function __construct(
        private readonly int    $id,
        private readonly int    $tenantId,
        private ?int            $contactId,
        private string          $title,
        private LeadStatus      $status,
        private ?string         $source,
        private string          $value,
        private ?string         $expectedCloseDate,
        private ?int            $assignedTo,
        private ?string         $notes,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getContactId(): ?int { return $this->contactId; }
    public function getTitle(): string { return $this->title; }
    public function getStatus(): LeadStatus { return $this->status; }
    public function getSource(): ?string { return $this->source; }
    public function getValue(): string { return $this->value; }
    public function getExpectedCloseDate(): ?string { return $this->expectedCloseDate; }
    public function getAssignedTo(): ?int { return $this->assignedTo; }
    public function getNotes(): ?string { return $this->notes; }
    public function qualify(): void {
        if ($this->status->isClosed()) throw new \DomainException('Cannot qualify a closed lead.');
        $this->status = LeadStatus::QUALIFIED;
    }
    public function markWon(): void { $this->status = LeadStatus::WON; }
    public function markLost(string $reason = ''): void { $this->status = LeadStatus::LOST; }
    public function advance(): void {
        $this->status = match($this->status) {
            LeadStatus::NEW         => LeadStatus::CONTACTED,
            LeadStatus::CONTACTED   => LeadStatus::QUALIFIED,
            LeadStatus::QUALIFIED   => LeadStatus::PROPOSAL,
            LeadStatus::PROPOSAL    => LeadStatus::NEGOTIATION,
            LeadStatus::NEGOTIATION => LeadStatus::WON,
            default => throw new \DomainException("Cannot advance lead from status: {$this->status->value}"),
        };
    }
}
