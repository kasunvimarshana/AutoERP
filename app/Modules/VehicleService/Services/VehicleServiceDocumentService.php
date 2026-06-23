<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\VehicleService\Models\VehicleServiceDocument;
use Modules\VehicleService\Models\VehicleServiceJob;
use RuntimeException;
use Throwable;

final class VehicleServiceDocumentService
{
    public function create(
        VehicleServiceJob $job,
        string $documentType,
        UploadedFile $file,
        ?string $description,
        ?int $uploadedBy,
    ): VehicleServiceDocument {
        $disk = (string) config('vehicle-service.documents.disk', 'tenant_private');
        $originalFilename = Str::limit(basename($file->getClientOriginalName()), 255, '');
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $size = $file->getSize();
        $temporaryPath = $file->getRealPath();

        if (! is_int($size) || $size < 1 || $temporaryPath === false) {
            throw new RuntimeException('Uploaded document metadata is invalid.');
        }

        $checksum = hash_file('sha256', $temporaryPath);
        if (! is_string($checksum) || strlen($checksum) !== 64) {
            throw new RuntimeException('Document checksum could not be calculated.');
        }

        $extension = $file->guessExtension();
        $storedFilename = (string) Str::uuid().($extension !== null ? '.'.$extension : '');
        $directory = sprintf(
            'tenants/%d/vehicle-service/jobs/%d/documents',
            (int) $job->tenant_id,
            (int) $job->getKey(),
        );
        $storagePath = $file->storeAs($directory, $storedFilename, $disk);

        if (! is_string($storagePath) || $storagePath === '') {
            throw new RuntimeException('Document could not be stored.');
        }

        try {
            return VehicleServiceDocument::query()->create([
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->getKey(),
                'document_type' => $documentType,
                'storage_disk' => $disk,
                'storage_path' => $storagePath,
                'original_filename' => $originalFilename,
                'mime_type' => $mimeType,
                'size_bytes' => $size,
                'checksum_sha256' => $checksum,
                'description' => $description,
                'uploaded_by' => $uploadedBy,
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storagePath);
            throw $exception;
        }
    }

    /** @return array{stream: resource, filename: string, mime_type: string} */
    public function open(VehicleServiceDocument $document): array
    {
        $stream = Storage::disk((string) $document->storage_disk)
            ->readStream((string) $document->storage_path);

        if (! is_resource($stream)) {
            throw new RuntimeException('Stored document could not be opened.');
        }

        return [
            'stream' => $stream,
            'filename' => (string) $document->original_filename,
            'mime_type' => (string) $document->mime_type,
        ];
    }

    public function delete(VehicleServiceDocument $document): void
    {
        $disk = Storage::disk((string) $document->storage_disk);
        $path = (string) $document->storage_path;

        if (! $document->delete()) {
            throw new RuntimeException('Document record could not be deleted.');
        }

        try {
            if ($disk->exists($path) && ! $disk->delete($path)) {
                throw new RuntimeException('Stored document could not be deleted.');
            }
        } catch (Throwable $exception) {
            $document->restore();
            throw $exception;
        }
    }
}
