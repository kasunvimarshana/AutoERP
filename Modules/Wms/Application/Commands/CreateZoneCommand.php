<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Commands;

final readonly class CreateZoneCommand
{
    public function __construct(
        public int $tenantId,
        public int $warehouseId,
        public string $name,
        public string $code,
        public ?string $description,
        public int $sortOrder,
    ) {}

    public function rules(): array
    {
        return [
            'tenantId' => ['required', 'integer', 'min:1'],
            'warehouseId' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'sortOrder' => ['required', 'integer', 'min:0'],
        ];
    }

    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'warehouseId' => $this->warehouseId,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'sortOrder' => $this->sortOrder,
        ];
    }
}
