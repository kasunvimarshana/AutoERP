<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\OrganizationUnit\Constants\OrganizationUnitPermission;
use Modules\OrganizationUnit\Http\Requests\ListOrganizationUnitDocumentRequest;
use Modules\OrganizationUnit\Http\Requests\OrganizationUnitVersionRequest;
use Modules\OrganizationUnit\Http\Requests\UpsertOrganizationUnitDocumentRequest;
use Modules\OrganizationUnit\Http\Resources\OrganizationUnitDocumentResource;
use Modules\OrganizationUnit\Http\Responses\OrganizationUnitApiResponder;
use Modules\OrganizationUnit\Services\Authorization\OrganizationUnitAuthorizationService;
use Modules\OrganizationUnit\Services\OrganizationUnitDocuments\OrganizationUnitDocumentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OrganizationUnitDocumentController extends Controller
{
    public function __construct(
        private readonly OrganizationUnitDocumentService $documents,
        private readonly OrganizationUnitAuthorizationService $authorization,
    ) {}

    public function index(ListOrganizationUnitDocumentRequest $request, int $organizationUnit): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::DOCUMENTS_VIEW);
        $filters = $request->validated();
        unset($filters['page'], $filters['per_page']);
        $result = $this->documents->page($organizationUnit, $filters, $request->perPage(), $request->page());
        if ($result->isFailure()) {
            return OrganizationUnitApiResponder::error($result->errorOrFail());
        }
        $page = $result->valueOrFail();
        abort_unless($page instanceof PagedResult, 500);
        return response()->json([
            'data' => OrganizationUnitDocumentResource::collection($page->items)->resolve($request),
            'meta' => $page->paginationMeta(),
        ]);
    }

    public function show(ListOrganizationUnitDocumentRequest $request, int $organizationUnit, int|string $document): JsonResponse|OrganizationUnitDocumentResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::DOCUMENTS_VIEW);
        $result = $this->documents->get($organizationUnit, $document);
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitDocumentResource($result->valueOrFail());
    }

    public function store(UpsertOrganizationUnitDocumentRequest $request, int $organizationUnit): JsonResponse|OrganizationUnitDocumentResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::DOCUMENTS_MANAGE);
        $result = $this->documents->create($organizationUnit, $this->payload($request));
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : (new OrganizationUnitDocumentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertOrganizationUnitDocumentRequest $request, int $organizationUnit, int|string $document): JsonResponse|OrganizationUnitDocumentResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::DOCUMENTS_MANAGE);
        $result = $this->documents->update($organizationUnit, $document, $this->payload($request));
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitDocumentResource($result->valueOrFail());
    }

    public function destroy(OrganizationUnitVersionRequest $request, int $organizationUnit, int|string $document): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::DOCUMENTS_MANAGE);
        $result = $this->documents->delete($organizationUnit, $document, (int) $request->validated('expected_version'));
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : response()->json(null, 204);
    }

    public function download(ListOrganizationUnitDocumentRequest $request, int $organizationUnit, int|string $document): JsonResponse|StreamedResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::DOCUMENTS_VIEW);
        $result = $this->documents->download($organizationUnit, $document);
        if ($result->isFailure()) {
            return OrganizationUnitApiResponder::error($result->errorOrFail());
        }
        $download = $result->valueOrFail();
        $record = $download['record'] ?? null;
        $stream = $download['stream'] ?? null;
        abort_unless($record instanceof DataRecord && is_resource($stream), 500);
        return response()->streamDownload(
            static function () use ($stream): void {
                try { fpassthru($stream); } finally { fclose($stream); }
            },
            (string) $record->require('original_filename'),
            [
                'Content-Type' => (string) $record->require('mime_type'),
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    private function payload(UpsertOrganizationUnitDocumentRequest $request): array
    {
        $payload = $request->validated();
        $file = $request->file('file');
        unset($payload['file']);
        if ($file instanceof UploadedFile) {
            $temporaryPath = $file->getRealPath();
            abort_unless(is_string($temporaryPath) && $temporaryPath !== '', 422, 'A valid document file is required.');
            $payload['file_tmp_path'] = $temporaryPath;
            $payload['file_original_name'] = $file->getClientOriginalName();
        }
        return $payload;
    }
}
