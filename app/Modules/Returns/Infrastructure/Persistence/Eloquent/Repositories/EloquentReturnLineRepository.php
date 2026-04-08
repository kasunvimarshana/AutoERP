<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Returns\Domain\Entities\ReturnLine;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnLineRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\ReturnLineModel;

class EloquentReturnLineRepository implements ReturnLineRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?ReturnLine
    {
        $model = ReturnLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByReturn(string $tenantId, string $returnType, string $returnId): array
    {
        return ReturnLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('return_type', $returnType)
            ->where('return_id', $returnId)
            ->get()
            ->map(fn (ReturnLineModel $model): ReturnLine => $this->mapToEntity($model))
            ->all();
    }

    public function save(ReturnLine $line): void
    {
        $model = ReturnLineModel::withoutGlobalScopes()->findOrNew($line->id);
        $model->fill([
            'tenant_id'     => $line->tenantId,
            'return_type'   => $line->returnType,
            'return_id'     => $line->returnId,
            'product_id'    => $line->productId,
            'variant_id'    => $line->variantId,
            'quantity'      => $line->quantity,
            'unit_price'    => $line->unitPrice,
            'line_total'    => $line->lineTotal,
            'batch_number'  => $line->batchNumber,
            'lot_number'    => $line->lotNumber,
            'serial_number' => $line->serialNumber,
            'condition'     => $line->condition,
            'restockable'   => $line->restockable,
            'quality_notes' => $line->qualityNotes,
        ]);

        if (! $model->exists) {
            $model->id = $line->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        ReturnLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(ReturnLineModel $model): ReturnLine
    {
        return new ReturnLine(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            returnType: (string) $model->return_type,
            returnId: (string) $model->return_id,
            productId: (string) $model->product_id,
            variantId: $model->variant_id !== null ? (string) $model->variant_id : null,
            quantity: (float) $model->quantity,
            unitPrice: (float) $model->unit_price,
            lineTotal: (float) $model->line_total,
            batchNumber: $model->batch_number !== null ? (string) $model->batch_number : null,
            lotNumber: $model->lot_number !== null ? (string) $model->lot_number : null,
            serialNumber: $model->serial_number !== null ? (string) $model->serial_number : null,
            condition: (string) $model->condition,
            restockable: (bool) $model->restockable,
            qualityNotes: $model->quality_notes !== null ? (string) $model->quality_notes : null,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
