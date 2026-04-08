<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Entities;

use DateTimeInterface;

class Setting
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $key,
        public readonly string $value,
        public readonly string $group,
        public readonly string $type,
        public readonly bool $isPublic,
        public readonly ?string $description,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function getValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'float'   => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($this->value, true),
            default   => $this->value,
        };
    }

    public function setValue(mixed $value): self
    {
        $encoded = match ($this->type) {
            'json', 'array' => json_encode($value, JSON_THROW_ON_ERROR),
            'boolean'       => $value ? 'true' : 'false',
            default         => (string) $value,
        };

        return new self(
            $this->id,
            $this->tenantId,
            $this->key,
            $encoded,
            $this->group,
            $this->type,
            $this->isPublic,
            $this->description,
            $this->createdAt,
            now(),
        );
    }
}
