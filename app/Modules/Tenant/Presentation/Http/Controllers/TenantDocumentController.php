<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\Contracts\UseCases\Documents\TenantDocumentServiceInterface;
use Modules\Tenant\Presentation\Http\Requests\ListTenantDocumentRequest;
use Modules\Tenant\Presentation\Http\Requests\UpsertTenantDocumentRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantDocumentResource;

final class TenantDocumentController extends Controller
{
    public function __construct(private readonly TenantDocumentServiceInterface $documents)
    {
    }

    public function index(ListTenantDocumentRequest $request): JsonResponse
    {
        $result = $this->documents->listByTenant((int) $request->validated('tenant_id'));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => TenantDocumentResource::collection($result->valueOrFail())->resolve()]);
    }

    public function show(int|string $tenantDocument): JsonResponse|TenantDocumentResource
    {
        $result = $this->documents->get($tenantDocument);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TenantDocumentResource($result->valueOrFail());
    }

    public function store(UpsertTenantDocumentRequest $request): JsonResponse|TenantDocumentResource
    {
        $result = $this->documents->create($this->preparePayload($request));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TenantDocumentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(
        UpsertTenantDocumentRequest $request,
        int|string $tenantDocument,
    ): JsonResponse|TenantDocumentResource {
        $result = $this->documents->update($tenantDocument, $this->preparePayload($request));
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'TENANT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new TenantDocumentResource($result->valueOrFail());
    }

    public function destroy(int|string $tenantDocument): JsonResponse
    {
        $result = $this->documents->delete($tenantDocument);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function preparePayload(UpsertTenantDocumentRequest $request): array
    {
        $payload = $request->validated();
        $upload = $request->file('file_upload');

        if ($upload instanceof UploadedFile) {
            unset($payload['file_upload']);

            $payload['file_tmp_path'] = $upload->getRealPath();
            $payload['file_original_name'] = $upload->getClientOriginalName();
            $payload['mime_type'] = $payload['mime_type'] ?? $upload->getClientMimeType();
            $payload['size'] = $payload['size'] ?? $upload->getSize();
        }

        return $payload;
    }
}
