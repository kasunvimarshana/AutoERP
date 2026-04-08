<?php

declare(strict_types=1);

namespace Modules\Financial\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class AccountData extends BaseDto
{
    public ?string $id = null;
    public ?string $parentId = null;
    public string $code = '';
    public string $name = '';
    public string $type = 'asset';
    public ?string $subType = null;
    public string $normalBalance = 'debit';
    public string $currencyCode = 'USD';
    public ?string $description = null;
    public bool $isActive = true;
    public bool $isSystem = false;
    public ?array $metadata = null;

    /**
     * Validation rules for creating/updating an account.
     */
    public function rules(): array
    {
        return [
            'code'           => ['required', 'string', 'max:50'],
            'name'           => ['required', 'string', 'max:200'],
            'type'           => ['required', 'string', 'in:asset,liability,equity,revenue,expense'],
            'normal_balance' => ['required', 'string', 'in:debit,credit'],
            'currency_code'  => ['sometimes', 'string', 'max:10'],
            'parent_id'      => ['nullable', 'string'],
            'is_active'      => ['sometimes', 'boolean'],
        ];
    }
}
