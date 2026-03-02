<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Commands;

final readonly class UpdateBinCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?string $description,
        public ?int $maxCapacity,
        public bool $isActive,
    ) {}

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'tenantId' => ['required', 'integer', 'min:1'],
            'maxCapacity' => ['nullable', 'integer', 'min:1'],
            'isActive' => ['required', 'boolean'],
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'description' => $this->description,
            'maxCapacity' => $this->maxCapacity,
            'isActive' => $this->isActive,
        ];
    }
}
