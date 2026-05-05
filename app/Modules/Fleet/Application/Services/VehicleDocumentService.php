<?php

declare(strict_types=1);

namespace Modules\Fleet\Application\Services;

use Modules\Fleet\Application\Contracts\VehicleDocumentServiceInterface;
use Modules\Fleet\Application\DTOs\CreateVehicleDocumentDTO;
use Modules\Fleet\Domain\Entities\VehicleDocument;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleDocumentRepositoryInterface;

class VehicleDocumentService implements VehicleDocumentServiceInterface
{
    public function __construct(
        private readonly VehicleDocumentRepositoryInterface $repo,
    ) {}

    public function create(CreateVehicleDocumentDTO $dto): VehicleDocument
    {
        $entity = new VehicleDocument(
            tenantId:         $dto->tenantId,
            vehicleId:        $dto->vehicleId,
            documentType:     $dto->documentType,
            documentNumber:   $dto->documentNumber,
            issuingAuthority: $dto->issuingAuthority,
            issueDate:        $dto->issueDate,
            expiryDate:       $dto->expiryDate,
            filePath:         $dto->filePath,
            notes:            $dto->notes,
            isActive:         $dto->isActive,
        );

        return $this->repo->save($entity);
    }

    public function update(int $id, array $data): VehicleDocument
    {
        $entity = $this->repo->find($id);

        if ($entity === null) {
            throw new \RuntimeException("VehicleDocument {$id} not found.");
        }

        $updated = new VehicleDocument(
            tenantId:         $entity->tenantId,
            vehicleId:        $entity->vehicleId,
            documentType:     $data['document_type'] ?? $entity->documentType,
            documentNumber:   $data['document_number'] ?? $entity->documentNumber,
            issuingAuthority: $data['issuing_authority'] ?? $entity->issuingAuthority,
            issueDate:        $data['issue_date'] ?? $entity->issueDate,
            expiryDate:       $data['expiry_date'] ?? $entity->expiryDate,
            filePath:         $data['file_path'] ?? $entity->filePath,
            notes:            $data['notes'] ?? $entity->notes,
            isActive:         $data['is_active'] ?? $entity->isActive,
            id:               $entity->id,
        );

        return $this->repo->save($updated);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    public function find(int $id): ?VehicleDocument
    {
        return $this->repo->find($id);
    }

    public function listByVehicle(int $vehicleId): array
    {
        return $this->repo->listByVehicle($vehicleId);
    }

    public function listExpiringSoon(int $tenantId, int $days = 30): array
    {
        return $this->repo->listExpiringSoon($tenantId, $days);
    }
}
