<?php

declare(strict_types=1);

namespace Modules\Extension\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Extension\Application\UseCases\Attachments\CreateAttachmentService;
use Modules\Extension\Application\UseCases\Attachments\DeleteAttachmentService;
use Modules\Extension\Application\UseCases\Attachments\GetAttachmentService;
use Modules\Extension\Application\UseCases\Attachments\ListAttachmentsService;
use Modules\Extension\Application\UseCases\Attachments\UpdateAttachmentService;
use Modules\Extension\Presentation\Http\Requests\ListAttachmentRequest;
use Modules\Extension\Presentation\Http\Requests\UpsertAttachmentRequest;
use Modules\Extension\Presentation\Http\Resources\AttachmentResource;

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
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
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
