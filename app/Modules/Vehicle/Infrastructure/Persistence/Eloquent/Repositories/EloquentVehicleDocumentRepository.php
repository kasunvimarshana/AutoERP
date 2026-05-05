<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleDocumentModel;

class EloquentVehicleDocumentRepository implements VehicleDocumentRepositoryInterface
{
    public function __construct(private readonly VehicleDocumentModel $documentModel) {}

    public function upsertByType(int $tenantId, int $vehicleId, string $documentType, array $data): void
    {
        $this->documentModel->newQuery()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'vehicle_id' => $vehicleId,
                'document_type' => $documentType,
            ],
            [
                'document_number' => $data['document_number'] ?? null,
                'issued_at' => $data['issued_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'file_path' => $data['file_path'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]
        );
    }

    public function listExpiring(int $tenantId, int $days): iterable
    {
        return $this->documentModel->newQuery()
            ->with('vehicle')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->orderBy('expires_at')
            ->get();
    }
}
