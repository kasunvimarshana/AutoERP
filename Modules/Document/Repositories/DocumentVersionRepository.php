<?php

declare(strict_types=1);

namespace Modules\Document\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Document\Models\DocumentVersion;

/**
 * DocumentVersion Repository
 *
 * Handles data access for document versions
 */
class DocumentVersionRepository extends BaseRepository
{
    public function __construct(DocumentVersion $model)
    {
        parent::__construct($model);
    }

    /**
     * Get versions for document
     */
    public function getByDocument(string $documentId): Collection
    {
        return $this->model->where('document_id', $documentId)
            ->orderByDesc('version_number')
            ->get();
    }

    /**
     * Get latest version
     */
    public function getLatest(string $documentId): ?DocumentVersion
    {
        return $this->model->where('document_id', $documentId)
            ->orderByDesc('version_number')
            ->first();
    }

    /**
     * Get version by number
     */
    public function getByVersionNumber(string $documentId, int $versionNumber): ?DocumentVersion
    {
        return $this->model->where('document_id', $documentId)
            ->where('version_number', $versionNumber)
            ->first();
    }

    /**
     * Get next version number
     */
    public function getNextVersionNumber(string $documentId): int
    {
        $latest = $this->getLatest($documentId);

        return $latest ? $latest->version_number + 1 : 1;
    }
}
