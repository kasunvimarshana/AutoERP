<?php

namespace App\Modules\Reporting\DTOs;

/**
 * Create Dashboard DTO
 */
class CreateDashboardDTO
{
    public function __construct(
        public string $name,
        public ?int $userId = null,
        public ?string $description = null,
        public ?array $layoutConfig = null,
        public bool $isDefault = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            userId: $data['user_id'] ?? null,
            description: $data['description'] ?? null,
            layoutConfig: $data['layout_config'] ?? null,
            isDefault: $data['is_default'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'user_id' => $this->userId,
            'description' => $this->description,
            'layout_config' => $this->layoutConfig,
            'is_default' => $this->isDefault,
        ];
    }
}
