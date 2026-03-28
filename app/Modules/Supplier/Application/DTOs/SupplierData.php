<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class SupplierData extends BaseDto
{
    public int $tenant_id;
    public string $name;
    public string $code;
    public ?int $user_id;
    public ?string $email;
    public ?string $phone;
    public ?array $address;
    public ?array $contact_person;
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
        $this->type     = 'other';
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'tenant_id'      => 'required|integer|exists:tenants,id',
            'name'           => 'required|string|max:255',
            'code'           => 'required|string|max:100',
            'user_id'        => 'nullable|integer|exists:users,id',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|array',
            'contact_person' => 'nullable|array',
            'payment_terms'  => 'nullable|string|max:100',
            'currency'       => 'nullable|string|size:3',
            'tax_number'     => 'nullable|string|max:100',
            'status'         => 'nullable|string|in:active,inactive,draft',
            'type'           => 'nullable|string|in:manufacturer,distributor,retailer,other',
            'attributes'     => 'nullable|array',
            'metadata'       => 'nullable|array',
        ];
    }
}
