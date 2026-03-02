<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Commands;

final readonly class CreateBinCommand
{
    public function __construct(
        public int $tenantId,
        public int $aisleId,
        public string $code,
        public ?string $description,
        public ?int $maxCapacity,
    ) {}

    public function rules(): array
    {
        return [
            'tenantId' => ['required', 'integer', 'min:1'],
            'aisleId' => ['required', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:100'],
            'maxCapacity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'aisleId' => $this->aisleId,
            'code' => $this->code,
            'description' => $this->description,
            'maxCapacity' => $this->maxCapacity,
        ];
    }
}
