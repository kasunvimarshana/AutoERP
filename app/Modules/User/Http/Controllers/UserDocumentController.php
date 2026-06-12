<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Modules\Extension\Http\Resources\AttachmentResource;
use Modules\Extension\Models\AttachmentModel;
use Modules\Extension\Services\Attachments\AttachmentService;
use Modules\User\Http\Requests\ListUserEntityRequest;
use Modules\User\Http\Requests\UpsertUserDocumentRequest;

final class UserDocumentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments) {}

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $filters = ['attachable_type' => 'user'];
        if (isset($validated['user_id'])) {
            $filters['attachable_id'] = (int) $validated['user_id'];
        }

        $result = $this->attachments->list(
            $filters,
            (int) ($validated['per_page'] ?? 25),
            (int) ($validated['page'] ?? 1),
        );
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $paginator = $result->valueOrFail();

        return response()->json([
            'data' => AttachmentResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int|string $userDocument): JsonResponse|AttachmentResource
    {
        $attachment = $this->attachment($userDocument);

        return $attachment instanceof JsonResponse ? $attachment : new AttachmentResource($attachment);
    }

    public function store(UpsertUserDocumentRequest $request): JsonResponse|AttachmentResource
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile) {
            return response()->json(['message' => 'A file is required.'], 422);
        }

        $result = $this->attachments->create([
            ...$this->metadataPayload($request),
            'attachable_type' => 'user',
            'attachable_id' => (int) $request->validated('user_id'),
        ], $file);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new AttachmentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(
        UpsertUserDocumentRequest $request,
        int|string $userDocument,
    ): JsonResponse|AttachmentResource {
        $existing = $this->attachment($userDocument);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }

        if (
            $request->filled('user_id')
            && (int) $request->validated('user_id') !== (int) $existing->attachable_id
        ) {
            return response()->json(['message' => 'Attachment ownership cannot be changed.'], 422);
        }

        $file = $request->file('file');
        $result = $file instanceof UploadedFile
            ? $this->attachments->createVersion($userDocument, $this->metadataPayload($request), $file)
            : $this->attachments->update($userDocument, [
                ...$this->metadataPayload($request),
                'row_version' => (int) $request->validated('row_version'),
            ]);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return new AttachmentResource($result->valueOrFail());
    }

    public function destroy(int|string $userDocument): JsonResponse
    {
        $existing = $this->attachment($userDocument);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }

        $result = $this->attachments->delete($userDocument);

        return $result->isFailure()
            ? response()->json(['message' => $result->errorOrFail()->message], 422)
            : response()->json(null, 204);
    }

    private function attachment(int|string $id): AttachmentModel|JsonResponse
    {
        $result = $this->attachments->get($id);
        if ($result->isFailure()) {
            return response()->json(['message' => 'User attachment not found.'], 404);
        }

        $attachment = $result->valueOrFail();
        if (! $attachment instanceof AttachmentModel || $attachment->attachable_type !== 'user') {
            return response()->json(['message' => 'User attachment not found.'], 404);
        }

        return $attachment;
    }

    private function metadataPayload(UpsertUserDocumentRequest $request): array
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
