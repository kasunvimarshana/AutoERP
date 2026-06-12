<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Extension\Http\Resources\AttachmentResource;
use Modules\Extension\Models\AttachmentModel;
use Modules\Extension\Services\Attachments\AttachmentService;
use Modules\Tenant\Http\Requests\ListTenantDocumentRequest;
use Modules\Tenant\Http\Requests\UpsertTenantDocumentRequest;

final class TenantDocumentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments) {}

    public function index(ListTenantDocumentRequest $request): JsonResponse
    {
        $result = $this->attachments->list([
            'attachable_type' => 'tenant',
            'attachable_id' => (int) $request->validated('tenant_id'),
        ], 100, 1);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json([
            'data' => AttachmentResource::collection($result->valueOrFail()->items())->resolve(),
        ]);
    }

    public function show(int|string $tenantDocument): JsonResponse|AttachmentResource
    {
        $attachment = $this->attachment($tenantDocument);

        return $attachment instanceof JsonResponse ? $attachment : new AttachmentResource($attachment);
    }

    public function store(UpsertTenantDocumentRequest $request): JsonResponse|AttachmentResource
    {
        $file = $request->file('file_upload');
        if (! $file instanceof UploadedFile) {
            return response()->json(['message' => 'A file is required.'], 422);
        }

        $result = $this->attachments->create(
            $this->payload($request, (int) $request->validated('tenant_id')),
            $file,
        );

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new AttachmentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(
        UpsertTenantDocumentRequest $request,
        int|string $tenantDocument,
    ): JsonResponse|AttachmentResource {
        $existing = $this->attachment($tenantDocument);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }

        $file = $request->file('file_upload');
        $result = $file instanceof UploadedFile
            ? $this->attachments->createVersion($tenantDocument, $this->metadataPayload($request), $file)
            : $this->attachments->update($tenantDocument, [
                ...$this->metadataPayload($request),
                'row_version' => (int) $request->validated('row_version'),
            ]);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return new AttachmentResource($result->valueOrFail());
    }

    public function destroy(int|string $tenantDocument): JsonResponse
    {
        $existing = $this->attachment($tenantDocument);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }

        $result = $this->attachments->delete($tenantDocument);

        return $result->isFailure()
            ? response()->json(['message' => $result->errorOrFail()->message], 422)
            : response()->json(null, 204);
    }

    private function attachment(int|string $id): AttachmentModel|JsonResponse
    {
        $result = $this->attachments->get($id);
        if ($result->isFailure()) {
            return response()->json(['message' => 'Tenant attachment not found.'], 404);
        }

        $attachment = $result->valueOrFail();
        if (! $attachment instanceof AttachmentModel || $attachment->attachable_type !== 'tenant') {
            return response()->json(['message' => 'Tenant attachment not found.'], 404);
        }

        return $attachment;
    }

    private function payload(UpsertTenantDocumentRequest $request, int $tenantId): array
    {
        return [
            ...$this->metadataPayload($request),
            'attachable_type' => 'tenant',
            'attachable_id' => $tenantId,
        ];
    }

    private function metadataPayload(UpsertTenantDocumentRequest $request): array
    {
        $validated = $request->validated();
        $payload = [];
        if (array_key_exists('name', $validated)) {
            $payload['display_name'] = $validated['name'];
        }
        if (array_key_exists('is_public', $validated)) {
            $payload['visibility'] = $validated['is_public'] ? 'public' : 'private';
        }
        if (array_key_exists('type', $validated)) {
            $payload['category'] = $this->category((string) $validated['type']);
        }
        if (array_key_exists('metadata', $validated) || array_key_exists('type', $validated)) {
            $payload['metadata'] = array_filter([
                ...($validated['metadata'] ?? []),
                'legacy_type' => $validated['type'] ?? null,
            ], static fn (mixed $value): bool => $value !== null);
        }

        return $payload;
    }

    private function category(?string $type): string
    {
        return in_array($type, (array) config('extension.attachments.categories', []), true)
            ? $type
            : 'general';
    }
}
