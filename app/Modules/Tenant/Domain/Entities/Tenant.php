<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

use DateTimeInterface;

class Tenant
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $domain,
        public readonly string $slug,
        public readonly string $status,
        public readonly string $plan,
        public readonly array $settings,
        public readonly array $metadata,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function activate(): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->domain,
            $this->slug,
            'active',
            $this->plan,
            $this->settings,
            $this->metadata,
            $this->createdAt,
            $this->updatedAt,
        );
    }

    public function suspend(): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->domain,
            $this->slug,
            'suspended',
            $this->plan,
            $this->settings,
            $this->metadata,
            $this->createdAt,
            $this->updatedAt,
        );
    }

    public function canAccessFeature(string $feature): bool
    {
        return isset($this->settings['features'][$feature])
            && $this->settings['features'][$feature] === true;
    }
}
