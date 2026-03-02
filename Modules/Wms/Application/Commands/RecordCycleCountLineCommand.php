<?php

declare(strict_types=1);

namespace Modules\Wms\Application\Commands;

final readonly class RecordCycleCountLineCommand
{
    public function __construct(
        public int $cycleCountId,
        public int $tenantId,
        public int $productId,
        public ?int $binId,
        public string $systemQty,
        public string $countedQty,
        public ?string $notes,
    ) {}

    public function rules(): array
    {
        return [
            'cycleCountId' => ['required', 'integer', 'min:1'],
            'tenantId' => ['required', 'integer', 'min:1'],
            'productId' => ['required', 'integer', 'min:1'],
            'binId' => ['nullable', 'integer', 'min:1'],
            'systemQty' => ['required', 'numeric', 'min:0'],
            'countedQty' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function toArray(): array
    {
        return [
            'cycleCountId' => $this->cycleCountId,
            'tenantId' => $this->tenantId,
            'productId' => $this->productId,
            'binId' => $this->binId,
            'systemQty' => $this->systemQty,
            'countedQty' => $this->countedQty,
            'notes' => $this->notes,
        ];
    }
}
