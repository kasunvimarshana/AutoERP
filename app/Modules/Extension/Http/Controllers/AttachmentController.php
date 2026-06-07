<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Extension\Http\Requests\ListAttachmentRequest;
use Modules\Extension\Http\Requests\UpsertAttachmentRequest;
use Modules\Extension\Http\Resources\AttachmentResource;
use Modules\Extension\Services\Attachments\CreateAttachmentService;
use Modules\Extension\Services\Attachments\DeleteAttachmentService;
use Modules\Extension\Services\Attachments\GetAttachmentService;
use Modules\Extension\Services\Attachments\ListAttachmentsService;
use Modules\Extension\Services\Attachments\UpdateAttachmentService;

final class AttachmentController extends Controller
{
    public function __construct(
        private readonly ListAttachmentsService $listService,
        private readonly GetAttachmentService $getService,
        private readonly CreateAttachmentService $createService,
        private readonly UpdateAttachmentService $updateService,
        private readonly DeleteAttachmentService $deleteService,
    ) {}

    public function index(ListAttachmentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => AttachmentResource::collection($pageResult->items)->resolve(),
            'meta' => $pageResult->paginationMeta(),
        ]);
    }

    public function show(int|string $id): JsonResponse|AttachmentResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new AttachmentResource($result->valueOrFail());
    }

    public function store(UpsertAttachmentRequest $request): JsonResponse|AttachmentResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new AttachmentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertAttachmentRequest $request, int|string $id): JsonResponse|AttachmentResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'EXTENSION_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new AttachmentResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
