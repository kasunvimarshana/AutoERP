<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Fleet\Domain\Entities\VehicleDocument;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleDocumentRepositoryInterface;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Models\VehicleDocumentModel;

class EloquentVehicleDocumentRepository implements VehicleDocumentRepositoryInterface
{
    public function find(int $id): ?VehicleDocument
    {
        $model = VehicleDocumentModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function listByVehicle(int $vehicleId): array
    {
        return VehicleDocumentModel::withoutGlobalScope('tenant')
            ->where('vehicle_id', $vehicleId)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function listExpiringSoon(int $tenantId, int $days = 30): array
    {
        return VehicleDocumentModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days)->toDateString())
            ->where('expiry_date', '>=', now()->toDateString())
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function save(VehicleDocument $document): VehicleDocument
    {
        $data = [
            'tenant_id'         => $document->tenantId,
            'vehicle_id'        => $document->vehicleId,
            'document_type'     => $document->documentType,
            'document_number'   => $document->documentNumber,
            'issuing_authority' => $document->issuingAuthority,
            'issue_date'        => $document->issueDate,
            'expiry_date'       => $document->expiryDate,
            'file_path'         => $document->filePath,
            'notes'             => $document->notes,
            'is_active'         => $document->isActive,
        ];

        if ($document->id !== null) {
            $model = VehicleDocumentModel::findOrFail($document->id);
            $model->update($data);
        } else {
            $model = VehicleDocumentModel::create($data);
        }

        return $this->toEntity($model->fresh());
    }

    public function delete(int $id): void
    {
        VehicleDocumentModel::findOrFail($id)->delete();
    }

    private function toEntity(VehicleDocumentModel $m): VehicleDocument
    {
        return new VehicleDocument(
            tenantId:         $m->tenant_id,
            vehicleId:        $m->vehicle_id,
            documentType:     $m->document_type,
            documentNumber:   $m->document_number,
            issuingAuthority: $m->issuing_authority,
            issueDate:        $m->issue_date,
            expiryDate:       $m->expiry_date,
            filePath:         $m->file_path,
            notes:            $m->notes,
            isActive:         (bool) $m->is_active,
            id:               $m->id,
        );
    }
}
