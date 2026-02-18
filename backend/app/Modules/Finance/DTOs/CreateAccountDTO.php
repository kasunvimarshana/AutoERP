<?php

namespace App\Modules\Finance\DTOs;

use App\Modules\Finance\Enums\AccountType;

class CreateAccountDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public AccountType $type,
        public ?int $parentId = null,
        public ?int $currencyId = null,
        public float $openingBalance = 0.0,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            type: $data['type'] instanceof AccountType ? $data['type'] : AccountType::from($data['type']),
            parentId: $data['parent_id'] ?? null,
            currencyId: $data['currency_id'] ?? null,
            openingBalance: $data['opening_balance'] ?? 0.0,
            description: $data['description'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type->value,
            'parent_id' => $this->parentId,
            'currency_id' => $this->currencyId,
            'opening_balance' => $this->openingBalance,
            'current_balance' => $this->openingBalance,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];
    }
}
