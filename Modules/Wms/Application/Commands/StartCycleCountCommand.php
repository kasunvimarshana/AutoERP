<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Commands;

final readonly class StartCycleCountCommand
{
    public function __construct(
        public int $tenantId,
        public int $warehouseId,
        public ?string $notes,
    ) {}

    public function rules(): array
    {
        return [
            'tenantId' => ['required', 'integer', 'min:1'],
            'warehouseId' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'warehouseId' => $this->warehouseId,
            'notes' => $this->notes,
        ];
    }
}
