<?php declare(strict_types=1);

namespace Modules\Asset\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Asset\Application\Contracts\ManageAssetDocumentServiceInterface;
use Modules\Asset\Domain\Entities\AssetDocument;
use Modules\Asset\Domain\RepositoryInterfaces\AssetDocumentRepositoryInterface;

class ManageAssetDocumentService implements ManageAssetDocumentServiceInterface
{
    public function __construct(
        private readonly AssetDocumentRepositoryInterface $documents,
    ) {}

    public function create(array $data): AssetDocument
    {
        return DB::transaction(function () use ($data): AssetDocument {
            $document = new AssetDocument(
                id: Str::uuid()->toString(),
                tenantId: (string) $data['tenant_id'],
                assetId: $data['asset_id'],
                documentType: $data['document_type'],
                documentNumber: $data['document_number'] ?? null,
                issueDate: new \DateTime($data['issue_date']),
                expiryDate: new \DateTime($data['expiry_date']),
                issuingAuthority: $data['issuing_authority'] ?? null,
                fileUrl: $data['file_url'] ?? null,
            );

            $this->documents->create($document);
            return $document;
        });
    }

    public function update(int $tenantId, string $id, array $data): AssetDocument
    {
        return DB::transaction(function () use ($tenantId, $id, $data): AssetDocument {
            $document = $this->documents->findById($id);
            if (!$document || $document->getTenantId() !== (string) $tenantId) {
                throw new \Exception('Document not found');
            }

            $this->documents->update($document);
            return $this->documents->findById($id);
        });
    }

    public function find(int $tenantId, string $id): AssetDocument
    {
        $document = $this->documents->findById($id);
        if (!$document || $document->getTenantId() !== (string) $tenantId) {
            throw new \Exception('Document not found');
        }
        return $document;
    }

    public function delete(int $tenantId, string $id): void
    {
        $this->find($tenantId, $id);
        $this->documents->delete($id);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): array
    {
        return $this->documents->getAllByTenant((string) $tenantId, $page, $perPage);
    }

    public function getExpiring(int $tenantId, int $daysThreshold = 30): array
    {
        return $this->documents->getExpiring((string) $tenantId, $daysThreshold);
    }
}
