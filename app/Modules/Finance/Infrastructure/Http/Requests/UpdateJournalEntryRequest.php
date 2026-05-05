<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

class UpdateJournalEntryRequest extends StoreJournalEntryRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge(['row_version' => ['required', 'integer', 'min:1']], parent::rules());
    }
}
