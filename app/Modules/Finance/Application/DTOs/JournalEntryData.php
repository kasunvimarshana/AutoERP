<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class JournalEntryData extends BaseDto
{
    public string  $entry_date;
    public ?string $description  = null;
    public string  $currency     = 'USD';
    public ?string $source_type  = null;
    public ?int    $source_id    = null;
    public ?array  $metadata     = null;
    /** @var JournalEntryLineData[] */
    public array   $lines        = [];

    public function rules(): array
    {
        return [
            'entry_date'          => ['required', 'date'],
            'description'         => ['nullable', 'string'],
            'currency'            => ['string', 'size:3'],
            'source_type'         => ['nullable', 'string', 'max:100'],
            'source_id'           => ['nullable', 'integer'],
            'metadata'            => ['nullable', 'array'],
            'lines'               => ['required', 'array', 'min:2'],
            'lines.*.account_id'  => ['required', 'integer'],
            'lines.*.debit_amount'  => ['numeric', 'min:0'],
            'lines.*.credit_amount' => ['numeric', 'min:0'],
        ];
    }

    /**
     * Hydrate line items as JournalEntryLineData DTOs.
     *
     * @return JournalEntryLineData[]
     */
    public function getLineData(): array
    {
        return array_map(
            static fn (array $line) => JournalEntryLineData::fromArray($line),
            $this->lines
        );
    }
}
