<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class JournalEntryLineData extends BaseDto
{
    public int    $account_id;
    public float  $debit_amount    = 0.0;
    public float  $credit_amount   = 0.0;
    public string $currency        = 'USD';
    public float  $exchange_rate   = 1.0;
    public int    $sort_order      = 0;
    public ?string $description    = null;
    public ?array  $metadata       = null;

    public function rules(): array
    {
        return [
            'account_id'    => ['required', 'integer'],
            'debit_amount'  => ['numeric', 'min:0'],
            'credit_amount' => ['numeric', 'min:0'],
            'currency'      => ['string', 'size:3'],
            'exchange_rate' => ['numeric', 'min:0'],
            'sort_order'    => ['integer'],
            'description'   => ['nullable', 'string'],
            'metadata'      => ['nullable', 'array'],
        ];
    }
}
