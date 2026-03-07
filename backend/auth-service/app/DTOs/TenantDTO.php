<?php

namespace App\DTOs;

class TenantDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $domain = null,
        public readonly array $settings = [],
        public readonly bool $isActive = true,
        public readonly string $plan = 'basic',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            domain: $data['domain'] ?? null,
            settings: $data['settings'] ?? [],
            isActive: $data['is_active'] ?? true,
            plan: $data['plan'] ?? 'basic',
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'settings' => $this->settings,
            'is_active' => $this->isActive,
            'plan' => $this->plan,
        ];
    }
}
