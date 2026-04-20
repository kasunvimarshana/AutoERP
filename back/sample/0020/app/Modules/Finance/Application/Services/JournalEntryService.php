<?php

namespace App\Modules\Finance\Application\Services;

use App\Modules\Finance\Domain\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Exception;

class JournalEntryService {
    /**
     * Creates a balanced journal entry with multiple lines.
     * 
     * @param array $data {
     *   posting_date: date,
     *   reference_no: string,
     *   description: string,
     *   lines: [
     *     { account_id: int, debit: float, credit: float, memo: string }
     *   ]
     * }
     */
    public function createBalancedEntry(array $data): JournalEntry {
        $lines = collect($data['lines']);
        $debits = $lines->sum('debit');
        $credits = $lines->sum('credit');

        // Use bcsub for precision decimal math
        if (abs($debits - $credits) > 0.0001) {
            throw new Exception("Journal entry is unbalanced. Debits: $debits, Credits: $credits");
        }

        return DB::transaction(function() use ($data) {
            $entry = JournalEntry::create([
                'tenant_id' => auth()->user()->tenant_id,
                'posting_date' => $data['posting_date'],
                'reference_no' => $data['reference_no'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => auth()->id()
            ]);

            foreach ($data['lines'] as $line) {
                $entry->lines()->create($line);
            }

            return $entry;
        });
    }
}
