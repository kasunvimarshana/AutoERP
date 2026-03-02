<?php
declare(strict_types=1);
namespace Modules\CRM\Domain\Entities;
class Opportunity {
    public function __construct(
        private readonly int    $id,
        private readonly int    $tenantId,
        private ?int            $leadId,
        private ?int            $contactId,
        private string          $title,
        private string          $stage,
        private string          $value,
        private string          $probability,
        private ?string         $expectedCloseDate,
        private ?int            $assignedTo,
        private ?string         $notes,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getLeadId(): ?int { return $this->leadId; }
    public function getContactId(): ?int { return $this->contactId; }
    public function getTitle(): string { return $this->title; }
    public function getStage(): string { return $this->stage; }
    public function getValue(): string { return $this->value; }
    public function getProbability(): string { return $this->probability; }
    public function getExpectedCloseDate(): ?string { return $this->expectedCloseDate; }
    public function getAssignedTo(): ?int { return $this->assignedTo; }
    public function getNotes(): ?string { return $this->notes; }
    public function getWeightedValue(): string {
        // probability is stored as decimal fraction 0-1 (e.g. 0.7500 = 75%)
        return bcmul($this->value, $this->probability, 4);
    }
}
