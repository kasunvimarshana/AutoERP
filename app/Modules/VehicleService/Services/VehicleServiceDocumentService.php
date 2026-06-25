<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\VehicleService\Models\VehicleServiceDocument;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceDocumentService
{
    public function create(
        VehicleServiceJob $job,
        string $documentType,
        ?UploadedFile $file,
        ?string $filePath,
        ?string $description,
        ?int $uploadedBy,
    ): VehicleServiceDocument {
        $path = $file?->store('vehicle-service-documents', 'public') ?? $filePath;

        return VehicleServiceDocument::query()->create([
            'tenant_id' => $job->tenant_id,
            'organization_unit_id' => $job->organization_unit_id,
            'vehicle_service_job_id' => $job->getKey(),
            'document_type' => $documentType,
            'file_path' => $path,
            'description' => $description,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function delete(VehicleServiceDocument $document): void
    {
        if ($document->file_path !== null && str_starts_with($document->file_path, 'vehicle-service-documents/')) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();
    }
}
