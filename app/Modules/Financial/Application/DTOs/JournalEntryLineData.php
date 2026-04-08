<?php

declare(strict_types=1);

namespace Modules\Financial\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class JournalEntryLineData extends BaseDto
{
    public ?string $id = null;
    public string $accountId = '';
    public ?string $description = null;
    public float $debit = 0.0;
    public float $credit = 0.0;
    public string $currencyCode = 'USD';
    public float $exchangeRate = 1.0;
    public ?string $reference = null;
    public ?array $metadata = null;

    /**
     * Validation rules for a journal entry line.
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'string'],
            'debit'      => ['required', 'numeric', 'min:0'],
            'credit'     => ['required', 'numeric', 'min:0'],
        ];
    }
}
