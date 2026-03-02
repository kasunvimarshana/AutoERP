<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Commands;

final readonly class UpdateAisleCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public ?string $description,
        public int $sortOrder,
        public bool $isActive,
    ) {}

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'tenantId' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'sortOrder' => ['required', 'integer', 'min:0'],
            'isActive' => ['required', 'boolean'],
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'name' => $this->name,
            'description' => $this->description,
            'sortOrder' => $this->sortOrder,
            'isActive' => $this->isActive,
        ];
    }
}
