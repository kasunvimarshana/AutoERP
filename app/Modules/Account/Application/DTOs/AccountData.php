<?php

declare(strict_types=1);

namespace Modules\Account\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class AccountData extends BaseDto
{
    public int $tenant_id;
    public string $code;
    public string $name;
    public string $type;
    public ?string $subtype;
    public ?string $description;
    public string $currency;
    public float $balance;
    public bool $is_system;
    public ?int $parent_id;
    public string $status;
    public ?array $attributes;
    public ?array $metadata;

    public function __construct()
    {
        $this->currency = 'USD';
        $this->balance = 0.0;
        $this->is_system = false;
        $this->status = 'active';
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'code'        => 'required|string|max:50',
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|in:asset,liability,equity,income,expense',
            'subtype'     => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'currency'    => 'nullable|string|size:3',
            'balance'     => 'nullable|numeric',
            'is_system'   => 'nullable|boolean',
            'parent_id'   => 'nullable|integer|exists:accounts,id',
            'status'      => 'nullable|string|in:active,inactive',
            'attributes'  => 'nullable|array',
            'metadata'    => 'nullable|array',
        ];
    }
}
