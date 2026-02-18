<?php

namespace App\Modules\Finance\DTOs;

class PostJournalEntryDTO
{
    public function __construct(
        public int $journalEntryId,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            journalEntryId: $data['journal_entry_id'],
        );
    }
}
