<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;
use Modules\Transaction\Domain\ValueObjects\TransactionStatus;
use Modules\Transaction\Domain\ValueObjects\TransactionType;

class UpdateTransactionData extends BaseDto
{
    public int $id;
    public ?string $transactionType = null;
    public ?float $amount = null;
    public ?string $transactionDate = null;
    public ?string $status = null;
    public ?string $currencyCode = null;
    public ?float $exchangeRate = null;
    public ?string $description = null;
    public ?string $referenceType = null;
    public ?int $referenceId = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'              => 'required|integer',
            'transactionType' => 'sometimes|required|string|in:'.implode(',', TransactionType::values()),
            'amount'          => 'sometimes|required|numeric|min:0',
            'transactionDate' => 'sometimes|required|date',
            'status'          => 'nullable|string|in:'.implode(',', TransactionStatus::values()),
            'currencyCode'    => 'nullable|string|size:3',
            'exchangeRate'    => 'nullable|numeric|min:0',
            'description'     => 'nullable|string',
            'referenceType'   => 'nullable|string|max:100',
            'referenceId'     => 'nullable|integer',
            'metadata'        => 'nullable|array',
        ];
    }
}
