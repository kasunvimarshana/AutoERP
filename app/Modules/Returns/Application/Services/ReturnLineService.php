<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Returns\Application\Contracts\ReturnLineServiceInterface;
use Modules\Returns\Domain\Entities\ReturnLine;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnLineRepositoryInterface;

class ReturnLineService implements ReturnLineServiceInterface
{
    public function __construct(
        private readonly ReturnLineRepositoryInterface $returnLineRepository,
    ) {}

    public function getReturnLine(string $tenantId, string $id): ReturnLine
    {
        $entity = $this->returnLineRepository->findById($tenantId, $id);

        if ($entity === null) {
            throw new NotFoundException("ReturnLine with id {$id} not found.");
        }

        return $entity;
    }

    public function getLinesForReturn(string $tenantId, string $returnType, string $returnId): array
    {
        return $this->returnLineRepository->findByReturn($tenantId, $returnType, $returnId);
    }

    public function addReturnLine(string $tenantId, array $data): ReturnLine
    {
        return DB::transaction(function () use ($tenantId, $data): ReturnLine {
            $now = now();

            $line = new ReturnLine(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                returnType: (string) $data['return_type'],
                returnId: (string) $data['return_id'],
                productId: (string) $data['product_id'],
                variantId: isset($data['variant_id']) ? (string) $data['variant_id'] : null,
                quantity: (float) $data['quantity'],
                unitPrice: (float) $data['unit_price'],
                lineTotal: (float) $data['line_total'],
                batchNumber: isset($data['batch_number']) ? (string) $data['batch_number'] : null,
                lotNumber: isset($data['lot_number']) ? (string) $data['lot_number'] : null,
                serialNumber: isset($data['serial_number']) ? (string) $data['serial_number'] : null,
                condition: (string) ($data['condition'] ?? 'good'),
                restockable: (bool) ($data['restockable'] ?? true),
                qualityNotes: isset($data['quality_notes']) ? (string) $data['quality_notes'] : null,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->returnLineRepository->save($line);

            return $line;
        });
    }

    public function updateReturnLine(string $tenantId, string $id, array $data): ReturnLine
    {
        return DB::transaction(function () use ($tenantId, $id, $data): ReturnLine {
            $existing = $this->getReturnLine($tenantId, $id);

            $updated = new ReturnLine(
                id: $existing->id,
                tenantId: $existing->tenantId,
                returnType: (string) ($data['return_type'] ?? $existing->returnType),
                returnId: (string) ($data['return_id'] ?? $existing->returnId),
                productId: (string) ($data['product_id'] ?? $existing->productId),
                variantId: array_key_exists('variant_id', $data) ? (isset($data['variant_id']) ? (string) $data['variant_id'] : null) : $existing->variantId,
                quantity: (float) ($data['quantity'] ?? $existing->quantity),
                unitPrice: (float) ($data['unit_price'] ?? $existing->unitPrice),
                lineTotal: (float) ($data['line_total'] ?? $existing->lineTotal),
                batchNumber: array_key_exists('batch_number', $data) ? (isset($data['batch_number']) ? (string) $data['batch_number'] : null) : $existing->batchNumber,
                lotNumber: array_key_exists('lot_number', $data) ? (isset($data['lot_number']) ? (string) $data['lot_number'] : null) : $existing->lotNumber,
                serialNumber: array_key_exists('serial_number', $data) ? (isset($data['serial_number']) ? (string) $data['serial_number'] : null) : $existing->serialNumber,
                condition: (string) ($data['condition'] ?? $existing->condition),
                restockable: isset($data['restockable']) ? (bool) $data['restockable'] : $existing->restockable,
                qualityNotes: array_key_exists('quality_notes', $data) ? (isset($data['quality_notes']) ? (string) $data['quality_notes'] : null) : $existing->qualityNotes,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->returnLineRepository->save($updated);

            return $updated;
        });
    }

    public function deleteReturnLine(string $tenantId, string $id): void
    {
        $this->returnLineRepository->delete($tenantId, $id);
    }
}
