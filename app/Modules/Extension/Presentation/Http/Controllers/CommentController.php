<?php

declare(strict_types=1);

namespace Modules\Extension\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Extension\Application\UseCases\Comments\CreateCommentService;
use Modules\Extension\Application\UseCases\Comments\DeleteCommentService;
use Modules\Extension\Application\UseCases\Comments\GetCommentService;
use Modules\Extension\Application\UseCases\Comments\ListCommentsService;
use Modules\Extension\Application\UseCases\Comments\UpdateCommentService;
use Modules\Extension\Presentation\Http\Requests\ListCommentRequest;
use Modules\Extension\Presentation\Http\Requests\UpsertCommentRequest;
use Modules\Extension\Presentation\Http\Resources\CommentResource;

final class CommentController extends Controller
{
    public function __construct(
        private readonly ListCommentsService $listService,
        private readonly GetCommentService $getService,
        private readonly CreateCommentService $createService,
        private readonly UpdateCommentService $updateService,
        private readonly DeleteCommentService $deleteService,
    ) {}

    public function index(ListCommentRequest $request): JsonResponse
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
            'data' => CommentResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|CommentResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new CommentResource($result->valueOrFail());
    }

    public function store(UpsertCommentRequest $request): JsonResponse|CommentResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new CommentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertCommentRequest $request, int|string $id): JsonResponse|CommentResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'EXTENSION_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new CommentResource($result->valueOrFail());
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
