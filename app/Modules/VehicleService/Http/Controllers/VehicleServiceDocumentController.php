<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceDocumentRequest;
use Modules\VehicleService\Http\Requests\VehicleServiceActionRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceDocumentResource;
use Modules\VehicleService\Services\VehicleServiceDocumentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VehicleServiceDocumentController extends VehicleServiceController
{
    public function options(ListVehicleServiceJobRequest $request, int $job): JsonResponse
    {
        $this->job($request, $job);
        $allowedTypes = config('vehicle-service.documents.allowed_types', []);
        $allowedMimeTypes = config('vehicle-service.documents.allowed_mime_types', []);
        $maxSizeKb = max((int) config('vehicle-service.documents.max_size_kb', 10240), 1);

        return response()->json(['data' => [
            'document_types' => is_array($allowedTypes) ? array_values($allowedTypes) : [],
            'mime_types' => is_array($allowedMimeTypes) ? array_values($allowedMimeTypes) : [],
            'max_size_bytes' => $maxSizeKb * 1024,
        ]]);
    }

    public function index(
        ListVehicleServiceJobRequest $request,
        int $job,
    ): AnonymousResourceCollection {
        return VehicleServiceDocumentResource::collection(
            $this->job($request, $job)->documents()->latest()->get(),
        );
    }

    public function store(
        StoreVehicleServiceDocumentRequest $request,
        int $job,
        VehicleServiceDocumentService $service,
    ): JsonResponse {
        $document = $service->create(
            $this->job($request, $job),
            (string) $request->input('document_type'),
            $request->file('file'),
            $request->filled('description') ? (string) $request->input('description') : null,
            $request->currentUserId(),
        );

        return (new VehicleServiceDocumentResource($document))->response()->setStatusCode(201);
    }

    public function download(
        ListVehicleServiceJobRequest $request,
        int $job,
        int $document,
        VehicleServiceDocumentService $service,
    ): StreamedResponse {
        $model = $this->job($request, $job)->documents()->findOrFail($document);
        $download = $service->open($model);
        $stream = $download['stream'];

        return response()->streamDownload(
            static function () use ($stream): void {
                try {
                    fpassthru($stream);
                } finally {
                    fclose($stream);
                }
            },
            $download['filename'],
            [
                'Content-Type' => $download['mime_type'],
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    public function destroy(
        VehicleServiceActionRequest $request,
        int $job,
        int $document,
        VehicleServiceDocumentService $service,
    ): JsonResponse {
        $model = $this->job($request, $job)->documents()->findOrFail($document);
        $service->delete($model);

        return response()->json(status: 204);
    }
}
