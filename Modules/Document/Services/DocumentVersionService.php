<?php

declare(strict_types=1);

namespace Modules\Document\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Document\Events\VersionCreated;
use Modules\Document\Exceptions\DocumentStorageException;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentVersion;
use Modules\Document\Repositories\DocumentRepository;
use Modules\Document\Repositories\DocumentVersionRepository;

/**
 * DocumentVersionService
 *
 * Manages document versions
 */
class DocumentVersionService
{
    private string $disk;

    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentVersionRepository $versionRepository,
    ) {
        $this->disk = config('document.storage.disk', 'local');
    }

    /**
     * Create a new version
     */
    public function createVersion(
        string $documentId,
        string $path,
        int $size,
        string $mimeType,
        ?string $comment = null
    ): DocumentVersion {
        return TransactionHelper::execute(function () use ($documentId, $path, $size, $mimeType, $comment) {
            $document = $this->documentRepository->findById($documentId);
            $versionNumber = $this->versionRepository->getNextVersionNumber($documentId);

            $version = $this->versionRepository->create([
                'document_id' => $documentId,
                'version_number' => $versionNumber,
                'path' => $path,
                'size' => $size,
                'mime_type' => $mimeType,
                'uploaded_by' => auth()->id(),
                'comment' => $comment,
                'metadata' => [
                    'created_at' => now()->toIso8601String(),
                ],
            ]);

            event(new VersionCreated($version));

            return $version;
        });
    }

    /**
     * List all versions for a document
     */
    public function listVersions(string $documentId): Collection
    {
        return $this->versionRepository->getByDocument($documentId);
    }

    /**
     * Get specific version
     */
    public function getVersion(string $documentId, int $versionNumber): ?DocumentVersion
    {
        return $this->versionRepository->getByVersionNumber($documentId, $versionNumber);
    }

    /**
     * Restore a previous version
     */
    public function restoreVersion(string $documentId, int $versionNumber, ?string $comment = null): Document
    {
        return TransactionHelper::execute(function () use ($documentId, $versionNumber, $comment) {
            $document = $this->documentRepository->findById($documentId);
            $version = $this->versionRepository->getByVersionNumber($documentId, $versionNumber);

            if (! $version) {
                throw new DocumentStorageException("Version {$versionNumber} not found");
            }

            if (! Storage::disk($this->disk)->exists($version->path)) {
                throw new DocumentStorageException('Version file not found in storage');
            }

            // Copy the old version file to new path
            $user = auth()->user();
            $extension = $document->extension;
            $filename = \Illuminate\Support\Str::ulid().'.'.$extension;

            $newPath = sprintf(
                '%s/%s/%s/%s/%s',
                $user->tenant_id,
                $user->organization_id,
                now()->format('Y'),
                now()->format('m'),
                $filename
            );

            Storage::disk($this->disk)->copy($version->path, $newPath);

            // Update document with restored version
            $document->update([
                'path' => $newPath,
                'size' => $version->size,
                'mime_type' => $version->mime_type,
                'version' => $this->versionRepository->getNextVersionNumber($documentId),
            ]);

            // Create new version entry
            $this->createVersion(
                $documentId,
                $newPath,
                $version->size,
                $version->mime_type,
                $comment ?? "Restored from version {$versionNumber}"
            );

            return $document->fresh();
        });
    }

    /**
     * Compare two versions
     */
    public function compareVersions(string $documentId, int $version1, int $version2): array
    {
        $v1 = $this->versionRepository->getByVersionNumber($documentId, $version1);
        $v2 = $this->versionRepository->getByVersionNumber($documentId, $version2);

        if (! $v1 || ! $v2) {
            throw new DocumentStorageException('One or both versions not found');
        }

        return [
            'version_1' => [
                'number' => $v1->version_number,
                'size' => $v1->size,
                'mime_type' => $v1->mime_type,
                'uploaded_by' => $v1->uploaded_by,
                'created_at' => $v1->created_at,
                'comment' => $v1->comment,
            ],
            'version_2' => [
                'number' => $v2->version_number,
                'size' => $v2->size,
                'mime_type' => $v2->mime_type,
                'uploaded_by' => $v2->uploaded_by,
                'created_at' => $v2->created_at,
                'comment' => $v2->comment,
            ],
            'differences' => [
                'size_change' => $v2->size - $v1->size,
                'time_between' => $v2->created_at->diffInSeconds($v1->created_at),
            ],
        ];
    }

    /**
     * Delete old versions
     */
    public function deleteOldVersions(string $documentId, int $keepLatest = 5): int
    {
        return TransactionHelper::execute(function () use ($documentId, $keepLatest) {
            $versions = $this->versionRepository->getByDocument($documentId);

            if ($versions->count() <= $keepLatest) {
                return 0;
            }

            $toDelete = $versions->slice($keepLatest);
            $count = 0;

            foreach ($toDelete as $version) {
                if (Storage::disk($this->disk)->exists($version->path)) {
                    Storage::disk($this->disk)->delete($version->path);
                }
                $version->delete();
                $count++;
            }

            return $count;
        });
    }

    /**
     * Get version by ID
     */
    public function findById(string $versionId): DocumentVersion
    {
        return $this->versionRepository->findOrFail($versionId);
    }

    /**
     * Download specific version
     */
    public function downloadVersion(string $versionId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $version = $this->findById($versionId);
        $document = $version->document;

        if (! Storage::disk($this->disk)->exists($version->path)) {
            throw new DocumentStorageException('Version file not found in storage');
        }

        return Storage::disk($this->disk)->download(
            $version->path,
            $document->original_name,
            [
                'Content-Type' => $version->mime_type,
            ]
        );
    }
}
