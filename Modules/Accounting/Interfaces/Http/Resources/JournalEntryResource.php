<?php

declare(strict_types=1);

namespace Modules\Accounting\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Domain\Entities\JournalEntry;

class JournalEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var JournalEntry $entry */
        $entry = $this->resource;

        return [
            'id' => $entry->id,
            'tenant_id' => $entry->tenantId,
            'entry_number' => $entry->entryNumber,
            'entry_date' => $entry->entryDate,
            'reference' => $entry->reference,
            'description' => $entry->description,
            'currency' => $entry->currency,
            'status' => $entry->status->value,
            'total_debit' => $entry->totalDebit,
            'total_credit' => $entry->totalCredit,
            'is_balanced' => $entry->isBalanced(),
            'lines' => array_map(
                fn ($line) => (new JournalEntryLineResource($line))->resolve(),
                $entry->lines
            ),
            'created_at' => $entry->createdAt,
            'updated_at' => $entry->updatedAt,
        ];
    }
}
