<?php

namespace App\Modules\Finance\DTOs;

use App\Modules\Finance\Enums\JournalEntryStatus;

class CreateJournalEntryDTO
{
    public function __construct(
        public string $entryNumber,
        public string $entryDate,
        public array $lines,
        public ?string $description = null,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public ?JournalEntryStatus $status = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            entryNumber: $data['entry_number'],
            entryDate: $data['entry_date'],
            lines: $data['lines'],
            description: $data['description'] ?? null,
            referenceType: $data['reference_type'] ?? null,
            referenceId: $data['reference_id'] ?? null,
            status: isset($data['status'])
                ? ($data['status'] instanceof JournalEntryStatus ? $data['status'] : JournalEntryStatus::from($data['status']))
                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'entry_number' => $this->entryNumber,
            'entry_date' => $this->entryDate,
            'description' => $this->description,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'status' => $this->status?->value ?? JournalEntryStatus::DRAFT->value,
        ];
    }
}
