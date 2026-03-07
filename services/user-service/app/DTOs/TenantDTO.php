<?php

namespace App\DTOs;

class TenantDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $slug,
        public readonly ?string $domain,
        public readonly string  $plan     = 'free',
        public readonly string  $status   = 'active',
        public readonly array   $settings = [],
        public readonly ?int    $maxUsers = null,
        public readonly array   $metadata = [],
        public readonly ?int    $id       = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name:     $data['name'],
            slug:     $data['slug'],
            domain:   $data['domain']    ?? null,
            plan:     $data['plan']      ?? 'free',
            status:   $data['status']    ?? 'active',
            settings: $data['settings']  ?? [],
            maxUsers: $data['max_users'] ?? null,
            metadata: $data['metadata']  ?? [],
            id:       $data['id']        ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id'        => $this->id,
            'name'      => $this->name,
            'slug'      => $this->slug,
            'domain'    => $this->domain,
            'plan'      => $this->plan,
            'status'    => $this->status,
            'settings'  => $this->settings,
            'max_users' => $this->maxUsers,
            'metadata'  => $this->metadata,
        ], fn ($v) => $v !== null);
    }
}
