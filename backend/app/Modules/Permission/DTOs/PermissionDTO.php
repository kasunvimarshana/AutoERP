<?php

namespace App\Modules\Permission\DTOs;

class PermissionDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $guard_name = 'web',
        public ?string $module = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? null,
            guard_name: $data['guard_name'] ?? 'web',
            module: $data['module'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'module' => $this->module,
        ], fn($value) => !is_null($value));
    }
}
