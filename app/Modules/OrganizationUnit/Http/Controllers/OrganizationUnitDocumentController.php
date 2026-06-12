<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Extension\Http\Resources\AttachmentResource;
use Modules\Extension\Models\AttachmentModel;
use Modules\Extension\Services\Attachments\AttachmentService;
use Modules\OrganizationUnit\Http\Requests\ListOrganizationUnitDocumentRequest;
use Modules\OrganizationUnit\Http\Requests\UpsertOrganizationUnitDocumentRequest;

final class OrganizationUnitDocumentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments) {}

    public function index(ListOrganizationUnitDocumentRequest $request): JsonResponse
    {
        $filters = ['attachable_type' => 'organization_unit'];
        if ($request->filled('organization_unit_id')) {
            $filters['attachable_id'] = (int) $request->validated('organization_unit_id');
        }
        $result = $this->attachments->list($filters, 100, 1);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json([
            'data' => AttachmentResource::collection($result->valueOrFail()->items())->resolve(),
        ]);
    }

    public function show(int|string $organizationUnitDocument): JsonResponse|AttachmentResource
    {
        $attachment = $this->attachment($organizationUnitDocument);

        return $attachment instanceof JsonResponse ? $attachment : new AttachmentResource($attachment);
    }

    public function store(UpsertOrganizationUnitDocumentRequest $request): JsonResponse|AttachmentResource
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile) {
            return response()->json(['message' => 'A file is required.'], 422);
        }

        $result = $this->attachments->create([
            ...$this->metadataPayload($request),
            'attachable_type' => 'organization_unit',
            'attachable_id' => (int) $request->validated('organization_unit_id'),
        ], $file);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new AttachmentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(
        UpsertOrganizationUnitDocumentRequest $request,
        int|string $organizationUnitDocument,
    ): JsonResponse|AttachmentResource {
        $existing = $this->attachment($organizationUnitDocument);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }

        $file = $request->file('file');
        $result = $file instanceof UploadedFile
            ? $this->attachments->createVersion($organizationUnitDocument, $this->metadataPayload($request), $file)
            : $this->attachments->update($organizationUnitDocument, [
                ...$this->metadataPayload($request),
                'row_version' => (int) $request->validated('row_version'),
            ]);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return new AttachmentResource($result->valueOrFail());
    }

    public function destroy(int|string $organizationUnitDocument): JsonResponse
    {
        $existing = $this->attachment($organizationUnitDocument);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }

        $result = $this->attachments->delete($organizationUnitDocument);

        return $result->isFailure()
            ? response()->json(['message' => $result->errorOrFail()->message], 422)
            : response()->json(null, 204);
    }

    private function attachment(int|string $id): AttachmentModel|JsonResponse
    {
        $result = $this->attachments->get($id);
        if ($result->isFailure()) {
            return response()->json(['message' => 'Organization unit attachment not found.'], 404);
        }

        $attachment = $result->valueOrFail();
        if (! $attachment instanceof AttachmentModel || $attachment->attachable_type !== 'organization_unit') {
            return response()->json(['message' => 'Organization unit attachment not found.'], 404);
        }

        return $attachment;
    }

    private function metadataPayload(UpsertOrganizationUnitDocumentRequest $request): array
    {
        $validated = $request->validated();
        $payload = [];
        if (array_key_exists('name', $validated)) {
            $payload['display_name'] = $validated['name'];
        }
        if (array_key_exists('type', $validated)) {
            $type = (string) $validated['type'];
            $payload['category'] = in_array($type, (array) config('extension.attachments.categories', []), true)
                ? $type
                : 'general';
        }
        if (array_key_exists('metadata', $validated) || array_key_exists('type', $validated)) {
            $payload['metadata'] = array_filter([
                ...($validated['metadata'] ?? []),
                'legacy_type' => $validated['type'] ?? null,
            ], static fn (mixed $value): bool => $value !== null);
        }

        return $payload;
    }
}
