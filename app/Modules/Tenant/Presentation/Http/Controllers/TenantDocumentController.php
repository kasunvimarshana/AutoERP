<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\DTOs\TenantDocumentData;
use Modules\Tenant\Application\Services\TenantService;
use Modules\Tenant\Domain\Exceptions\TenantRecordNotFoundException;
use Modules\Tenant\Presentation\Http\Controllers\Concerns\HandlesTenantHttp;
use Modules\Tenant\Presentation\Http\Requests\StoreTenantDocumentRequest;
use Modules\Tenant\Presentation\Http\Requests\UpdateTenantDocumentRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantDocumentResource;

class TenantDocumentController extends Controller
{
    use HandlesTenantHttp;

    public function __construct(private readonly TenantService $tenants) {}

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return TenantDocumentResource::collection($this->tenants->listDocuments($tenant, $this->perPage($request)));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreTenantDocumentRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $document = $this->tenants->createDocument(TenantDocumentData::fromArray($tenant, $request->validated()));

            return (new TenantDocumentResource($document))->response()->setStatusCode(201);
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $document): TenantDocumentResource|JsonResponse
    {
        try {
            return new TenantDocumentResource($this->tenants->findDocument($tenant, $document));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateTenantDocumentRequest $request, int|string $tenant, int|string $document): TenantDocumentResource|JsonResponse
    {
        try {
            return new TenantDocumentResource($this->tenants->updateDocument($tenant, $document, TenantDocumentData::fromArray($tenant, $request->validated())));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $document): JsonResponse
    {
        try {
            $this->tenants->deleteDocument($tenant, $document);

            return response()->json(null, 204);
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
