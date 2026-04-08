<?php

declare(strict_types=1);

namespace Modules\Financial\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class JournalEntryData extends BaseDto
{
    public ?string $id = null;
    public ?string $fiscalYearId = null;
    public ?string $entryNumber = null;
    public string $entryDate = '';
    public ?string $postingDate = null;
    public string $type = 'manual';
    public string $status = 'draft';
    public ?string $description = null;
    public ?string $reference = null;
    public string $currencyCode = 'USD';
    public float $exchangeRate = 1.0;
    /** @var array<JournalEntryLineData> */
    public array $lines = [];
    public ?array $metadata = null;

    /**
     * Validation rules for creating a journal entry.
     */
    public function rules(): array
    {
        return [
            'entry_date'       => ['required', 'date'],
            'type'             => ['sometimes', 'string', 'in:manual,auto,adjustment,closing'],
            'currency_code'    => ['sometimes', 'string', 'max:10'],
            'lines'            => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'string'],
            'lines.*.debit'    => ['required', 'numeric', 'min:0'],
            'lines.*.credit'   => ['required', 'numeric', 'min:0'],
        ];
    }
}
