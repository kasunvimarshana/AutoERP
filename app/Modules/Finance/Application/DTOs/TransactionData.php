<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class TransactionData extends BaseDto
{
    public string  $type;
    public string  $transaction_date;
    public float   $amount;
    public string  $currency       = 'USD';
    public float   $exchange_rate  = 1.0;
    public ?int    $journal_entry_id = null;
    public ?int    $from_account_id  = null;
    public ?int    $to_account_id    = null;
    public ?string $description      = null;
    public ?string $category         = null;
    public ?array  $tags             = null;
    public ?string $contact_type     = null;
    public ?int    $contact_id       = null;
    public ?array  $attachments      = null;
    public ?array  $metadata         = null;

    public function rules(): array
    {
        return [
            'type'              => ['required', 'string', 'in:income,expense,transfer,payment,refund,adjustment'],
            'transaction_date'  => ['required', 'date'],
            'amount'            => ['required', 'numeric', 'min:0'],
            'currency'          => ['string', 'size:3'],
            'exchange_rate'     => ['numeric', 'min:0'],
            'journal_entry_id'  => ['nullable', 'integer'],
            'from_account_id'   => ['nullable', 'integer'],
            'to_account_id'     => ['nullable', 'integer'],
            'description'       => ['nullable', 'string'],
            'category'          => ['nullable', 'string', 'max:100'],
            'tags'              => ['nullable', 'array'],
            'contact_type'      => ['nullable', 'string', 'max:100'],
            'contact_id'        => ['nullable', 'integer'],
            'attachments'       => ['nullable', 'array'],
            'metadata'          => ['nullable', 'array'],
        ];
    }
}
