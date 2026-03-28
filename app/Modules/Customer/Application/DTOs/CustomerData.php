<?php

declare(strict_types=1);

namespace Modules\Customer\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class CustomerData extends BaseDto
{
    public int $tenant_id;
    public string $name;
    public string $code;
    public ?int $user_id;
    public ?string $email;
    public ?string $phone;
    public ?array $billing_address;
    public ?array $shipping_address;
    public ?string $date_of_birth;
    public ?string $loyalty_tier;
    public ?float $credit_limit;
    public ?string $payment_terms;
    public string $currency;
    public ?string $tax_number;
    public string $status;
    public string $type;
    public ?array $attributes;
    public ?array $metadata;

    public function __construct()
    {
        $this->currency = 'USD';
        $this->status   = 'active';
        $this->type     = 'retail';
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'tenant_id'        => 'required|integer|exists:tenants,id',
            'name'             => 'required|string|max:255',
            'code'             => 'required|string|max:100',
            'user_id'          => 'nullable|integer|exists:users,id',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'billing_address'  => 'nullable|array',
            'shipping_address' => 'nullable|array',
            'date_of_birth'    => 'nullable|string|max:20',
            'loyalty_tier'     => 'nullable|string|in:bronze,silver,gold,platinum',
            'credit_limit'     => 'nullable|numeric|min:0',
            'payment_terms'    => 'nullable|string|max:100',
            'currency'         => 'nullable|string|size:3',
            'tax_number'       => 'nullable|string|max:100',
            'status'           => 'nullable|string|in:active,inactive,draft',
            'type'             => 'nullable|string|in:retail,wholesale,corporate,vip,other',
            'attributes'       => 'nullable|array',
            'metadata'         => 'nullable|array',
        ];
    }
}
