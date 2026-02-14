<?php

namespace App\Modules\Customer\DTOs;

class CustomerDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $tenant_id = null,
        public ?string $branch_id = null,
        public ?string $code = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $mobile = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $postal_code = null,
        public ?string $tax_id = null,
        public ?string $customer_type = 'individual',
        public ?string $status = 'active',
        public ?string $notes = null,
        public ?array $settings = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            tenant_id: $data['tenant_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            code: $data['code'] ?? null,
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            mobile: $data['mobile'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            postal_code: $data['postal_code'] ?? null,
            tax_id: $data['tax_id'] ?? null,
            customer_type: $data['customer_type'] ?? 'individual',
            status: $data['status'] ?? 'active',
            notes: $data['notes'] ?? null,
            settings: $data['settings'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'branch_id' => $this->branch_id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'tax_id' => $this->tax_id,
            'customer_type' => $this->customer_type,
            'status' => $this->status,
            'notes' => $this->notes,
            'settings' => $this->settings,
        ], fn($value) => !is_null($value));
    }
}
