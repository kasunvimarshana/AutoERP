<?php

declare(strict_types=1);

namespace Modules\Financial\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class BankAccountData extends BaseDto
{
    public ?string $id = null;
    public ?string $accountId = null;
    public string $name = '';
    public ?string $accountNumber = null;
    public ?string $routingNumber = null;
    public ?string $bankName = null;
    public ?string $bankCode = null;
    public string $accountType = 'checking';
    public string $currencyCode = 'USD';
    public float $openingBalance = 0.0;
    public float $creditLimit = 0.0;
    public string $status = 'active';
    public ?array $metadata = null;

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:200'],
            'account_type' => ['sometimes', 'string', 'in:checking,savings,credit_card,line_of_credit'],
            'currency_code' => ['sometimes', 'string', 'max:10'],
            'status'       => ['sometimes', 'string', 'in:active,inactive,closed'],
        ];
    }
}
