<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;
use Modules\Transaction\Domain\ValueObjects\TransactionStatus;

class JournalEntryData extends BaseDto
{
    public int $tenantId;
    public int $transactionId;
    public string $accountCode;
    public string $accountName;
    public float $debitAmount = 0.0;
    public float $creditAmount = 0.0;
    public ?string $description = null;
    public string $status = TransactionStatus::DRAFT;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'      => 'required|integer',
            'transactionId' => 'required|integer',
            'accountCode'   => 'required|string|max:20',
            'accountName'   => 'required|string|max:255',
            'debitAmount'   => 'sometimes|numeric|min:0',
            'creditAmount'  => 'sometimes|numeric|min:0',
            'description'   => 'nullable|string',
            'status'        => 'sometimes|string|in:'.implode(',', TransactionStatus::values()),
            'metadata'      => 'nullable|array',
        ];
    }
}
