<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitDocumentData;
use Modules\OrganizationUnit\Application\Services\OrganizationUnitService;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitRecordNotFoundException;
use Modules\OrganizationUnit\Presentation\Http\Controllers\Concerns\HandlesOrganizationUnitHttp;
use Modules\OrganizationUnit\Presentation\Http\Requests\StoreOrganizationUnitDocumentRequest;
use Modules\OrganizationUnit\Presentation\Http\Requests\UpdateOrganizationUnitDocumentRequest;
use Modules\OrganizationUnit\Presentation\Http\Resources\OrganizationUnitDocumentResource;

class OrganizationUnitDocumentController extends Controller
{
    use HandlesOrganizationUnitHttp;

    public function __construct(private readonly OrganizationUnitService $organizationUnits) {}

    public function index(Request $request, int|string $tenant, int|string $unit): mixed
    {
        try {
            return OrganizationUnitDocumentResource::collection($this->organizationUnits->listDocuments($tenant, $unit, $this->perPage($request)));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreOrganizationUnitDocumentRequest $request, int|string $tenant, int|string $unit): JsonResponse
    {
        try {
            $document = $this->organizationUnits->createDocument(OrganizationUnitDocumentData::fromArray($tenant, $unit, $request->validated()));

            return (new OrganizationUnitDocumentResource($document))->response()->setStatusCode(201);
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $unit, int|string $document): OrganizationUnitDocumentResource|JsonResponse
    {
        try {
            return new OrganizationUnitDocumentResource($this->organizationUnits->findDocument($tenant, $unit, $document));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateOrganizationUnitDocumentRequest $request, int|string $tenant, int|string $unit, int|string $document): OrganizationUnitDocumentResource|JsonResponse
    {
        try {
            return new OrganizationUnitDocumentResource($this->organizationUnits->updateDocument($tenant, $unit, $document, OrganizationUnitDocumentData::fromArray($tenant, $unit, $request->validated())));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $unit, int|string $document): JsonResponse
    {
        try {
            $this->organizationUnits->deleteDocument($tenant, $unit, $document);

            return response()->json(null, 204);
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
