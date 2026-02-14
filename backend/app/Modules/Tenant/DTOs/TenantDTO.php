<?php

namespace App\Modules\Tenant\DTOs;

class TenantDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $plan = 'basic',
        public ?string $status = 'active',
        public ?string $domain = null,
        public ?array $settings = [],
        public ?string $trial_ends_at = null,
        public ?string $subscription_ends_at = null,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            plan: $data['plan'] ?? 'basic',
            status: $data['status'] ?? 'active',
            domain: $data['domain'] ?? null,
            settings: $data['settings'] ?? [],
            trial_ends_at: $data['trial_ends_at'] ?? null,
            subscription_ends_at: $data['subscription_ends_at'] ?? null,
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'plan' => $this->plan,
            'status' => $this->status,
            'settings' => $this->settings,
            'trial_ends_at' => $this->trial_ends_at,
            'subscription_ends_at' => $this->subscription_ends_at,
        ], fn($value) => !is_null($value));
    }
}
