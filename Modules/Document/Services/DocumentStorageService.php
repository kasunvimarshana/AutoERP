<?php

declare(strict_types=1);

namespace Modules\Document\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Document\Enums\AccessLevel;
use Modules\Document\Enums\DocumentStatus;
use Modules\Document\Enums\DocumentType;
use Modules\Document\Events\DocumentDeleted;
use Modules\Document\Events\DocumentUploaded;
use Modules\Document\Exceptions\DocumentStorageException;
use Modules\Document\Models\Document;
use Modules\Document\Repositories\DocumentRepository;

/**
 * DocumentStorageService
 *
 * Handles file storage operations using Laravel Storage
 */
class DocumentStorageService
{
    private string $disk;

    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentVersionService $versionService,
    ) {
        $this->disk = config('document.storage.disk', 'local');
    }

    /**
     * Upload a new document
     */
    public function upload(
        UploadedFile $file,
        ?string $folderId = null,
        ?string $description = null,
        ?DocumentStatus $status = null,
        ?AccessLevel $accessLevel = null
    ): Document {
        return TransactionHelper::execute(function () use ($file, $folderId, $description, $status, $accessLevel) {
            $this->validateFile($file);

            $user = auth()->user();
            $tenantId = $user->tenant_id;
            $organizationId = $user->organization_id;

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::ulid().'.'.$extension;

            // Store path structure: tenant_id/organization_id/year/month/filename
            $path = sprintf(
                '%s/%s/%s/%s/%s',
                $tenantId,
                $organizationId,
                now()->format('Y'),
                now()->format('m'),
                $filename
            );

            // Store file
            $storedPath = Storage::disk($this->disk)->putFileAs(
                dirname($path),
                $file,
                basename($path)
            );

            if (! $storedPath) {
                throw new DocumentStorageException('Failed to store file');
            }

            // Create document record
            $document = $this->documentRepository->create([
                'tenant_id' => $tenantId,
                'organization_id' => $organizationId,
                'folder_id' => $folderId,
                'owner_id' => $user->id,
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'description' => $description,
                'type' => DocumentType::FILE,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'path' => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'extension' => $extension,
                'version' => 1,
                'is_latest_version' => true,
                'access_level' => $accessLevel ?? AccessLevel::PRIVATE,
                'status' => $status ?? DocumentStatus::PUBLISHED,
                'metadata' => $this->extractMetadata($file),
                'download_count' => 0,
                'view_count' => 0,
            ]);

            // Create initial version
            $this->versionService->createVersion($document->id, $storedPath, $file->getSize(), $file->getMimeType(), 'Initial upload');

            event(new DocumentUploaded($document));

            return $document->fresh();
        });
    }

    /**
     * Get document file information for download
     */
    public function getForDownload(string $documentId): array
    {
        $document = $this->documentRepository->findById($documentId);

        if (! Storage::disk($this->disk)->exists($document->path)) {
            throw new DocumentStorageException('File not found in storage');
        }

        // Increment download count
        $document->incrementDownloadCount();

        return [
            'document' => $document,
            'disk' => $this->disk,
            'path' => $document->path,
            'filename' => $document->original_name,
            'mime_type' => $document->mime_type,
        ];
    }

    /**
     * Get document file information for streaming
     */
    public function getForStreaming(string $documentId): array
    {
        $document = $this->documentRepository->findById($documentId);

        if (! Storage::disk($this->disk)->exists($document->path)) {
            throw new DocumentStorageException('File not found in storage');
        }

        // Increment view count
        $document->incrementViewCount();

        return [
            'document' => $document,
            'disk' => $this->disk,
            'path' => $document->path,
            'filename' => $document->original_name,
            'mime_type' => $document->mime_type,
        ];
    }

    /**
     * Delete document
     */
    public function delete(string $documentId, bool $permanent = false): bool
    {
        return TransactionHelper::execute(function () use ($documentId, $permanent) {
            $document = $this->documentRepository->findById($documentId);

            if ($permanent) {
                // Delete physical file
                if (Storage::disk($this->disk)->exists($document->path)) {
                    Storage::disk($this->disk)->delete($document->path);
                }

                // Delete all versions
                foreach ($document->versions as $version) {
                    if (Storage::disk($this->disk)->exists($version->path)) {
                        Storage::disk($this->disk)->delete($version->path);
                    }
                }

                // Permanently delete record
                $document->forceDelete();
            } else {
                // Soft delete
                $document->delete();
            }

            event(new DocumentDeleted($document, $permanent));

            return true;
        });
    }

    /**
     * Move document to another folder
     */
    public function move(string $documentId, ?string $targetFolderId): Document
    {
        return TransactionHelper::execute(function () use ($documentId, $targetFolderId) {
            $document = $this->documentRepository->findById($documentId);

            $document->update(['folder_id' => $targetFolderId]);

            return $document->fresh();
        });
    }

    /**
     * Copy document
     */
    public function copy(string $documentId, ?string $targetFolderId = null, ?string $newName = null): Document
    {
        return TransactionHelper::execute(function () use ($documentId, $targetFolderId, $newName) {
            $source = $this->documentRepository->findById($documentId);

            if (! Storage::disk($this->disk)->exists($source->path)) {
                throw new DocumentStorageException('Source file not found');
            }

            $user = auth()->user();

            // Generate new path
            $extension = $source->extension;
            $filename = Str::ulid().'.'.$extension;

            $path = sprintf(
                '%s/%s/%s/%s/%s',
                $user->tenant_id,
                $user->organization_id,
                now()->format('Y'),
                now()->format('m'),
                $filename
            );

            // Copy physical file
            Storage::disk($this->disk)->copy($source->path, $path);

            // Create new document record
            $copy = $this->documentRepository->create([
                'tenant_id' => $source->tenant_id,
                'organization_id' => $source->organization_id,
                'folder_id' => $targetFolderId ?? $source->folder_id,
                'owner_id' => $user->id,
                'name' => $newName ?? ($source->name.' (Copy)'),
                'description' => $source->description,
                'type' => $source->type,
                'mime_type' => $source->mime_type,
                'size' => $source->size,
                'path' => $path,
                'original_name' => $source->original_name,
                'extension' => $source->extension,
                'version' => 1,
                'is_latest_version' => true,
                'access_level' => $source->access_level,
                'status' => DocumentStatus::DRAFT,
                'metadata' => $source->metadata,
                'download_count' => 0,
                'view_count' => 0,
            ]);

            // Create initial version for copy
            $this->versionService->createVersion($copy->id, $path, $source->size, $source->mime_type, 'Copied from original');

            return $copy->fresh();
        });
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        $maxSize = config('document.upload.max_size', 10485760); // 10MB default
        $allowedMimes = config('document.upload.allowed_mimes', []);

        if ($file->getSize() > $maxSize) {
            throw new DocumentStorageException('File size exceeds maximum allowed size of '.($maxSize / 1048576).'MB');
        }

        if (! empty($allowedMimes) && ! in_array($file->getMimeType(), $allowedMimes)) {
            throw new DocumentStorageException('File type not allowed');
        }
    }

    /**
     * Extract metadata from file
     */
    private function extractMetadata(UploadedFile $file): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_at' => now()->toIso8601String(),
        ];

        // Extract image metadata if it's an image
        if (Str::startsWith($file->getMimeType(), 'image/')) {
            try {
                $imageInfo = getimagesize($file->getPathname());
                if ($imageInfo) {
                    $metadata['width'] = $imageInfo[0];
                    $metadata['height'] = $imageInfo[1];
                }
            } catch (\Exception $e) {
                // Ignore errors in metadata extraction
            }
        }

        return $metadata;
    }

    /**
     * Get file URL
     */
    public function getUrl(string $documentId): string
    {
        $document = $this->documentRepository->findById($documentId);

        if ($this->disk === 'public') {
            return Storage::disk($this->disk)->url($document->path);
        }

        return Storage::disk($this->disk)->temporaryUrl(
            $document->path,
            now()->addMinutes(config('document.storage.url_expiry', 60))
        );
    }
}
