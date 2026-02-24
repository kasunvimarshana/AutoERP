<?php

namespace Modules\Accounting\Domain\Entities;

use Modules\Accounting\Domain\Enums\JournalEntryStatus;

class JournalEntry
{
    public function __construct(
        private readonly string             $id,
        private readonly string             $tenantId,
        private readonly string             $number,
        private readonly ?string            $reference,
        private readonly ?string            $description,
        private readonly string             $date,
        private readonly JournalEntryStatus $status,
        private readonly array              $lines,
        private readonly ?string            $createdBy,
    ) {}

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getNumber(): string { return $this->number; }
    public function getReference(): ?string { return $this->reference; }
    public function getDescription(): ?string { return $this->description; }
    public function getDate(): string { return $this->date; }
    public function getStatus(): JournalEntryStatus { return $this->status; }
    public function getLines(): array { return $this->lines; }
    public function getCreatedBy(): ?string { return $this->createdBy; }
}
