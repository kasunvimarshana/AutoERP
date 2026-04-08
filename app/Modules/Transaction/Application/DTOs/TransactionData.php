<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;
use Modules\Transaction\Domain\ValueObjects\TransactionStatus;
use Modules\Transaction\Domain\ValueObjects\TransactionType;

class TransactionData extends BaseDto
{
    public int $tenantId;
    public string $referenceNumber;
    public string $transactionType;
    public float $amount;
    public string $transactionDate;
    public string $status = TransactionStatus::DRAFT;
    public string $currencyCode = 'USD';
    public float $exchangeRate = 1.0;
    public ?string $description = null;
    public ?string $referenceType = null;
    public ?int $referenceId = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'        => 'required|integer',
            'referenceNumber' => 'required|string|max:100',
            'transactionType' => 'required|string|in:'.implode(',', TransactionType::values()),
            'amount'          => 'required|numeric|min:0',
            'transactionDate' => 'required|date',
            'status'          => 'sometimes|string|in:'.implode(',', TransactionStatus::values()),
            'currencyCode'    => 'sometimes|string|size:3',
            'exchangeRate'    => 'sometimes|numeric|min:0',
            'description'     => 'nullable|string',
            'referenceType'   => 'nullable|string|max:100',
            'referenceId'     => 'nullable|integer',
            'metadata'        => 'nullable|array',
        ];
    }
}
