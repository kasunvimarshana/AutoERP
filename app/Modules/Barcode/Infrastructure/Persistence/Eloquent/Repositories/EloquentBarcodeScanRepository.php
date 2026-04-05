<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Barcode\Domain\Entities\BarcodeScan;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodeScanRepositoryInterface;
use Modules\Barcode\Infrastructure\Persistence\Eloquent\Models\BarcodeScanModel;

class EloquentBarcodeScanRepository implements BarcodeScanRepositoryInterface
{
    public function __construct(private readonly BarcodeScanModel $model) {}

    private function hydrate(BarcodeScanModel $m): BarcodeScan
    {
        return new BarcodeScan(
            $m->id,
            $m->tenant_id,
            $m->barcode_definition_id,
            $m->scanned_value,
            $m->resolved_type,
            $m->scanned_by_user_id,
            $m->device_id,
            $m->location_tag,
            $m->metadata ?? [],
            $m->scanned_at,
        );
    }

    private function persist(BarcodeScan $scan): BarcodeScanModel
    {
        $data = [
            'tenant_id'             => $scan->getTenantId(),
            'barcode_definition_id' => $scan->getBarcodeDefinitionId(),
            'scanned_value'         => $scan->getScannedValue(),
            'resolved_type'         => $scan->getResolvedType(),
            'scanned_by_user_id'    => $scan->getScannedByUserId(),
            'device_id'             => $scan->getDeviceId(),
            'location_tag'          => $scan->getLocationTag(),
            'metadata'              => $scan->getMetadata(),
            'scanned_at'            => $scan->getScannedAt(),
        ];

        if ($scan->getId() === null) {
            return $this->model->newQuery()->create($data);
        }

        $m = $this->model->newQuery()->findOrFail($scan->getId());
        $m->update($data);

        return $m->fresh();
    }

    public function findById(int $id): ?BarcodeScan
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->hydrate($m) : null;
    }

    /** @return BarcodeScan[] */
    public function findByDefinition(int $tenantId, int $barcodeDefinitionId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('barcode_definition_id', $barcodeDefinitionId)
            ->get()
            ->map(fn($m) => $this->hydrate($m))
            ->all();
    }

    /** @return BarcodeScan[] */
    public function findByDateRange(int $tenantId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereBetween('scanned_at', [$from, $to])
            ->get()
            ->map(fn($m) => $this->hydrate($m))
            ->all();
    }

    public function save(BarcodeScan $barcodeScan): BarcodeScan
    {
        return $this->hydrate($this->persist($barcodeScan));
    }

    public function delete(int $id): void
    {
        $this->model->newQuery()->where('id', $id)->delete();
    }
}
