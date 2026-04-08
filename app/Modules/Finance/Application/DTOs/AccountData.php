<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class AccountData extends BaseDto
{
    public ?int    $parent_id           = null;
    public string  $code;
    public string  $name;
    public string  $type;
    public ?string $nature              = null;
    public ?string $classification      = null;
    public ?string $description         = null;
    public bool    $is_active           = true;
    public bool    $is_bank_account     = false;
    public bool    $is_system           = false;
    public ?string $bank_name           = null;
    public ?string $bank_account_number = null;
    public ?string $bank_routing_number = null;
    public string  $currency            = 'USD';
    public float   $opening_balance     = 0.0;
    public ?array  $metadata            = null;

    public function rules(): array
    {
        return [
            'code'                => ['required', 'string', 'max:20'],
            'name'                => ['required', 'string', 'max:255'],
            'type'                => ['required', 'string', 'in:asset,liability,equity,revenue,expense'],
            'nature'              => ['nullable', 'string', 'in:debit,credit'],
            'classification'      => ['nullable', 'string', 'max:100'],
            'description'         => ['nullable', 'string'],
            'is_active'           => ['boolean'],
            'is_bank_account'     => ['boolean'],
            'is_system'           => ['boolean'],
            'bank_name'           => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_routing_number' => ['nullable', 'string', 'max:50'],
            'currency'            => ['string', 'size:3'],
            'opening_balance'     => ['numeric'],
            'metadata'            => ['nullable', 'array'],
            'parent_id'           => ['nullable', 'integer'],
        ];
    }
}
