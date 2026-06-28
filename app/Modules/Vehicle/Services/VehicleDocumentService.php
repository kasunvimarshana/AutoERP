<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Vehicle\DTOs\VehicleDocumentData;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleDocument;
use Throwable;

final class VehicleDocumentService
{
    public function __construct(private readonly FileStorageServiceInterface $files) {}

    public function create(Vehicle $vehicle, VehicleDocumentData $data, ?callable $onFileStored = null): VehicleDocument
    {
        $storedPath = $this->storeUploadedFile($vehicle, $data);
        if ($storedPath !== null && $onFileStored !== null) {
            $onFileStored($storedPath);
        }

        try {
            return $vehicle->documents()->create($this->attributes($vehicle, $data, filePath: $storedPath));
        } catch (Throwable $exception) {
            $this->deleteStoredFile($storedPath);
            throw $exception;
        }
    }

    public function update(Vehicle $vehicle, VehicleDocument $document, VehicleDocumentData $data): VehicleDocument
    {
        $this->assertOwned($vehicle, $document);
        $oldPath = $document->file_path;
        $storedPath = $this->storeUploadedFile($vehicle, $data);

        try {
            $document->fill($this->attributes(
                $vehicle,
                $data,
                false,
                $storedPath,
                includeFilePath: $storedPath !== null,
            ))->save();
        } catch (Throwable $exception) {
            $this->deleteStoredFile($storedPath);
            throw $exception;
        }

        if ($storedPath !== null && is_string($oldPath) && $oldPath !== '' && $oldPath !== $storedPath) {
            $this->deleteStoredFile($oldPath, warnOnly: true);
        }

        return $document->refresh();
    }

    public function delete(Vehicle $vehicle, VehicleDocument $document): void
    {
        $this->assertOwned($vehicle, $document);
        $path = $document->file_path;
        $document->delete();
        if (is_string($path) && $path !== '') {
            $this->deleteStoredFile($path, warnOnly: true);
        }
    }

    /** @param list<VehicleDocumentData> $documents */
    public function replace(Vehicle $vehicle, array $documents, ?callable $onFileStored = null): void
    {
        $vehicle->documents()->delete();
        foreach ($documents as $document) { $this->create($vehicle, $document, $onFileStored); }
    }

    public function expiring(int $tenantId, ?int $organizationUnitId, int $days = 30)
    {
        return VehicleDocument::query()
            ->where('tenant_id', $tenantId)
            ->when($organizationUnitId !== null, fn ($query) => $query->where(fn ($scope) => $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId)))
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('expiry_date')
            ->get();
    }

    /** @return resource|false */
    public function stream(Vehicle $vehicle, VehicleDocument $document): mixed
    {
        $this->assertOwned($vehicle, $document);

        if (! is_string($document->file_path) || $document->file_path === '') {
            return false;
        }

        if (! $this->files->exists($document->file_path)) {
            Log::warning('Vehicle document file was not found for preview or download.', [
                'vehicle_id' => $vehicle->getKey(),
                'document_id' => $document->getKey(),
                'path' => $document->file_path,
            ]);

            return false;
        }

        return $this->files->readStream($document->file_path);
    }

    public function mimeType(Vehicle $vehicle, VehicleDocument $document): string
    {
        $this->assertOwned($vehicle, $document);
        if (! is_string($document->file_path) || $document->file_path === '') {
            return 'application/octet-stream';
        }

        $mime = $this->files->mimeType($document->file_path);

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }

    public function downloadName(VehicleDocument $document): string
    {
        $path = is_string($document->file_path) ? $document->file_path : '';
        $name = basename($path);

        return $name !== '' ? $name : 'vehicle-document.bin';
    }

    public function deleteStoredFile(?string $path, bool $warnOnly = false): void
    {
        if ($path === null || trim($path) === '') {
            return;
        }

        try {
            if (! $this->files->exists($path)) {
                if ($warnOnly) {
                    Log::warning('Vehicle document file was already missing.', ['path' => $path]);
                }

                return;
            }

            $this->files->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Unable to delete vehicle document file.', [
                'path' => $path,
                'exception' => $exception->getMessage(),
            ]);

            if (! $warnOnly) {
                throw $exception;
            }
        }
    }

    private function attributes(
        Vehicle $vehicle,
        VehicleDocumentData $data,
        bool $includeScope = true,
        ?string $filePath = null,
        bool $includeFilePath = true,
    ): array
    {
        return [
            ...($includeScope ? ['tenant_id' => $vehicle->tenant_id, 'organization_unit_id' => $vehicle->organization_unit_id] : []),
            'document_type' => $data->documentType,
            'document_number' => $data->documentNumber,
            'issued_date' => $data->issuedDate,
            'expiry_date' => $data->expiryDate,
            ...($includeFilePath ? ['file_path' => $filePath ?? $data->filePath] : []),
            'status' => $data->status,
            'notes' => $data->notes,
        ];
    }

    private function storeUploadedFile(Vehicle $vehicle, VehicleDocumentData $data): ?string
    {
        if ($data->file === null) {
            return null;
        }

        $realPath = $data->file->getRealPath();
        if ($realPath === false || ! is_file($realPath)) {
            throw new InvalidArgumentException('Uploaded vehicle document file is not readable.');
        }

        $extension = strtolower(trim((string) $data->file->getClientOriginalExtension()));
        if ($extension === '') {
            $extension = strtolower((string) ($data->file->guessExtension() ?: 'bin'));
        }

        return $this->files->store(
            $realPath,
            sprintf('vehicles/%d/%d/documents', (int) $vehicle->tenant_id, (int) $vehicle->getKey()),
            Str::uuid()->toString().'.'.$extension,
        );
    }

    private function assertOwned(Vehicle $vehicle, VehicleDocument $document): void
    {
        if ((int) $document->vehicle_id !== (int) $vehicle->getKey()) {
            throw new InvalidArgumentException('Vehicle document does not belong to the vehicle.');
        }
    }
}
