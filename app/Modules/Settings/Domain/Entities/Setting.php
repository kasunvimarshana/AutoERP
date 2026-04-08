<?php

declare(strict_types=1);

namespace Modules\Settings\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Settings\Domain\ValueObjects\SettingType;

class Setting
{
    private ?int $id;
    private int $tenantId;
    private string $groupKey;
    private string $settingKey;
    private string $settingType;
    private mixed $value;
    private mixed $defaultValue;
    private string $label;
    private ?string $description;
    private bool $isSystem;
    private bool $isEditable;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $groupKey,
        string $settingKey,
        string $label,
        mixed $value = null,
        mixed $defaultValue = null,
        string $settingType = SettingType::STRING,
        ?string $description = null,
        bool $isSystem = false,
        bool $isEditable = true,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id           = $id;
        $this->tenantId     = $tenantId;
        $this->groupKey     = $groupKey;
        $this->settingKey   = $settingKey;
        $this->label        = $label;
        $this->value        = $value;
        $this->defaultValue = $defaultValue;
        $this->settingType  = $settingType;
        $this->description  = $description;
        $this->isSystem     = $isSystem;
        $this->isEditable   = $isEditable;
        $this->metadata     = $metadata ?? new Metadata([]);
        $this->createdAt    = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt    = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getGroupKey(): string { return $this->groupKey; }
    public function getSettingKey(): string { return $this->settingKey; }
    public function getSettingType(): string { return $this->settingType; }
    public function getRawValue(): mixed { return $this->value; }
    public function getDefaultValue(): mixed { return $this->defaultValue; }
    public function getLabel(): string { return $this->label; }
    public function getDescription(): ?string { return $this->description; }
    public function isSystem(): bool { return $this->isSystem; }
    public function isEditable(): bool { return $this->isEditable; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function getValue(): mixed
    {
        return $this->getCastValue();
    }

    public function setValue(mixed $value): void
    {
        $this->value     = $value;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isBoolean(): bool
    {
        return $this->settingType === SettingType::BOOLEAN;
    }

    public function isJson(): bool
    {
        return $this->settingType === SettingType::JSON || $this->settingType === SettingType::ARRAY;
    }

    public function getCastValue(): mixed
    {
        if ($this->value === null) {
            return $this->value;
        }

        return match ($this->settingType) {
            SettingType::INTEGER => (int) $this->value,
            SettingType::FLOAT   => (float) $this->value,
            SettingType::BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            SettingType::JSON,
            SettingType::ARRAY   => is_array($this->value) ? $this->value : json_decode((string) $this->value, true),
            default              => (string) $this->value,
        };
    }

    public function updateDetails(
        string $groupKey,
        string $settingKey,
        string $label,
        mixed $value,
        mixed $defaultValue,
        string $settingType,
        ?string $description,
        bool $isSystem,
        bool $isEditable,
        ?Metadata $metadata,
    ): void {
        $this->groupKey     = $groupKey;
        $this->settingKey   = $settingKey;
        $this->label        = $label;
        $this->value        = $value;
        $this->defaultValue = $defaultValue;
        $this->settingType  = $settingType;
        $this->description  = $description;
        $this->isSystem     = $isSystem;
        $this->isEditable   = $isEditable;
        if ($metadata !== null) {
            $this->metadata = $metadata;
        }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
