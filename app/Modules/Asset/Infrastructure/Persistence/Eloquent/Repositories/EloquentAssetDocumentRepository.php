<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Asset\Domain\Entities\AssetDocument;
use Modules\Asset\Domain\RepositoryInterfaces\AssetDocumentRepositoryInterface;
use Modules\Asset\Infrastructure\Persistence\Eloquent\Models\AssetDocumentModel;

class EloquentAssetDocumentRepository implements AssetDocumentRepositoryInterface
{
    public function create(AssetDocument $document): void
    {
        AssetDocumentModel::create([
            'id' => $document->getId(),
            'tenant_id' => $document->getTenantId(),
            'asset_id' => $document->getAssetId(),
            'document_type' => $document->getDocumentType(),
            'document_name' => $document->getDocumentName(),
            'document_number' => $document->getDocumentNumber(),
            'issue_date' => $document->getIssueDate(),
            'expiry_date' => $document->getExpiryDate(),
            'is_active' => true,
        ]);
    }

    public function findById(string $id): ?AssetDocument
    {
        $model = AssetDocumentModel::find($id);
        return $model ? $this->toDomain($model) : null;
    }

    public function getByAsset(string $assetId, int $page = 1, int $limit = 50): array
    {
        $query = AssetDocumentModel::byAsset($assetId);
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
        ];
    }

    public function getByType(string $tenantId, string $type, int $page = 1, int $limit = 50): array
    {
        $query = AssetDocumentModel::where('tenant_id', $tenantId)->byType($type);
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn($m) => $this->toDomain($m), $data),
            'total' => $total,
        ];
    }

    public function getExpiringDocuments(string $tenantId, int $daysThreshold = 30): array
    {
        $data = AssetDocumentModel::where('tenant_id', $tenantId)
            ->expiring($daysThreshold)
            ->get()
            ->map(fn($m) => $this->toDomain($m))
            ->toArray();

        return $data;
    }

    public function getExpiredDocuments(string $tenantId): array
    {
        $data = AssetDocumentModel::where('tenant_id', $tenantId)
            ->expired()
            ->get()
            ->map(fn($m) => $this->toDomain($m))
            ->toArray();

        return $data;
    }

    public function update(AssetDocument $document): void
    {
        AssetDocumentModel::where('id', $document->getId())->update([
            'is_active' => $document->isActive(),
        ]);
    }

    public function delete(string $id): void
    {
        AssetDocumentModel::where('id', $id)->delete();
    }

    private function toDomain(AssetDocumentModel $model): AssetDocument
    {
        return new AssetDocument(
            $model->id,
            $model->tenant_id,
            $model->asset_id,
            $model->document_type,
            $model->document_name,
            $model->document_number,
            $model->issue_date,
            $model->expiry_date,
            $model->file_path,
            $model->file_url,
            $model->issuing_authority,
            $model->notes,
            $model->is_active,
        );
    }
}
