<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Constants\TenantPermission;
use Modules\Tenant\Http\Requests\TenantVersionRequest;
use Modules\Tenant\Http\Requests\UpsertTenantDocumentRequest;
use Modules\Tenant\Http\Resources\TenantDocumentResource;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Services\Documents\TenantDocumentService;
use Modules\Tenant\Services\TenantAuthorizationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TenantDocumentController extends Controller
{
    public function __construct(
        private readonly TenantAuthorizationService $authorization,
        private readonly CurrentTenantContextAccessorInterface $context,
        private readonly TenantDocumentService $documents,
    ) {}

    public function index(): JsonResponse
    {
        $this->requirePermission(TenantPermission::DOCUMENTS_VIEW);

        $result = $this->documents->list($this->tenantId());

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : response()->json([
                'data' => TenantDocumentResource::collection(
                    $result->valueOrFail(),
                )->resolve(),
            ]);
    }

    public function show(int|string $tenantDocument): JsonResponse|TenantDocumentResource
    {
        $this->requirePermission(TenantPermission::DOCUMENTS_VIEW);

        $result = $this->documents->get($this->tenantId(), $tenantDocument);

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : new TenantDocumentResource($result->valueOrFail());
    }

    public function store(
        UpsertTenantDocumentRequest $request,
    ): JsonResponse|TenantDocumentResource {
        $this->requirePermission(TenantPermission::DOCUMENTS_MANAGE);

        $result = $this->documents->create(
            $this->tenantId(),
            $this->payload($request),
        );
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        return (new TenantDocumentResource($result->valueOrFail()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpsertTenantDocumentRequest $request,
        int|string $tenantDocument,
    ): JsonResponse|TenantDocumentResource {
        $this->requirePermission(TenantPermission::DOCUMENTS_MANAGE);

        $result = $this->documents->update(
            $this->tenantId(),
            $tenantDocument,
            $this->payload($request),
        );

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : new TenantDocumentResource($result->valueOrFail());
    }

    public function destroy(
        TenantVersionRequest $request,
        int|string $tenantDocument,
    ): JsonResponse {
        $this->requirePermission(TenantPermission::DOCUMENTS_MANAGE);

        $result = $this->documents->delete(
            $this->tenantId(),
            $tenantDocument,
            (int) $request->validated('expected_version'),
        );

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : response()->json(null, 204);
    }

    public function download(
        int|string $tenantDocument,
    ): JsonResponse|StreamedResponse {
        $this->requirePermission(TenantPermission::DOCUMENTS_VIEW);

        $result = $this->documents->download($this->tenantId(), $tenantDocument);
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        $download = $result->valueOrFail();
        $record = $download['record'] ?? null;
        $stream = $download['stream'] ?? null;
        abort_unless($record instanceof DataRecord && is_resource($stream), 500);

        return response()->streamDownload(
            static function () use ($stream): void {
                try {
                    fpassthru($stream);
                } finally {
                    fclose($stream);
                }
            },
            (string) $record->require('original_filename'),
            [
                'Content-Type' => (string) $record->require('mime_type'),
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    /** @return array<string, mixed> */
    private function payload(UpsertTenantDocumentRequest $request): array
    {
        $payload = $request->validated();
        $file = $request->file('file');
        unset($payload['file']);

        if ($file instanceof UploadedFile) {
            $payload['file_tmp_path'] = $file->getRealPath();
            $payload['file_original_name'] = $file->getClientOriginalName();
        }

        return $payload;
    }

    private function tenantId(): int
    {
        return (int) $this->context->requireCurrent()->tenantId();
    }

    private function requirePermission(string $permission): void
    {
        abort_unless(
            $this->authorization->allows($permission),
            403,
            'You are not authorized to perform this action.',
        );
    }
}
