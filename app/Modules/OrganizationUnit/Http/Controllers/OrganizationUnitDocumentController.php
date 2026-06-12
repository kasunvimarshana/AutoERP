<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Http\Requests\ListOrganizationUnitDocumentRequest;
use Modules\OrganizationUnit\Http\Requests\UpsertOrganizationUnitDocumentRequest;
use Modules\OrganizationUnit\Http\Resources\OrganizationUnitDocumentResource;
use Modules\OrganizationUnit\Services\OrganizationUnitDocuments\OrganizationUnitDocumentService;

final class OrganizationUnitDocumentController extends Controller
{
    public function __construct(private readonly OrganizationUnitDocumentService $documents) {}

    public function index(ListOrganizationUnitDocumentRequest $request): JsonResponse
    {
        $result = $this->documents->listByTenant((int) $request->validated('tenant_id'));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => OrganizationUnitDocumentResource::collection($result->valueOrFail())->resolve()]);
    }

    public function show(int|string $organizationUnitDocument): JsonResponse|OrganizationUnitDocumentResource
    {
        $result = $this->documents->get($organizationUnitDocument);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new OrganizationUnitDocumentResource($result->valueOrFail());
    }

    public function store(UpsertOrganizationUnitDocumentRequest $request): JsonResponse|OrganizationUnitDocumentResource
    {
        $result = $this->documents->create($this->prepareMutationPayload($request));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new OrganizationUnitDocumentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertOrganizationUnitDocumentRequest $request, int|string $organizationUnitDocument): JsonResponse|OrganizationUnitDocumentResource
    {
        $result = $this->documents->update($organizationUnitDocument, $this->prepareMutationPayload($request));
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'ORGANIZATION_UNIT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new OrganizationUnitDocumentResource($result->valueOrFail());
    }

    public function destroy(int|string $organizationUnitDocument): JsonResponse
    {
        $result = $this->documents->delete($organizationUnitDocument);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareMutationPayload(UpsertOrganizationUnitDocumentRequest $request): array
    {
        $payload = $request->validated();
        $upload = $request->file('file');

        if ($upload instanceof UploadedFile) {
            unset($payload['file']);

            $payload['file_tmp_path'] = $upload->getRealPath();
            $payload['file_original_name'] = $upload->getClientOriginalName();
        }

        return $payload;
    }
}
