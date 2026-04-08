<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateJournalEntryData extends BaseDto
{
    public int $id;
    public ?string $accountCode = null;
    public ?string $accountName = null;
    public ?float $debitAmount = null;
    public ?float $creditAmount = null;
    public ?string $description = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'           => 'required|integer',
            'accountCode'  => 'sometimes|required|string|max:20',
            'accountName'  => 'sometimes|required|string|max:255',
            'debitAmount'  => 'nullable|numeric|min:0',
            'creditAmount' => 'nullable|numeric|min:0',
            'description'  => 'nullable|string',
            'metadata'     => 'nullable|array',
        ];
    }
}
