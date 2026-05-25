<?php

declare(strict_types=1);

namespace Modules\SystemUser\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\CreateSystemUserServiceInterface;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\DeleteSystemUserServiceInterface;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\GetSystemUserServiceInterface;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\ListSystemUsersServiceInterface;
use Modules\SystemUser\Application\Contracts\UseCases\SystemUsers\UpdateSystemUserServiceInterface;
use Modules\SystemUser\Presentation\Http\Requests\ListSystemUserRequest;
use Modules\SystemUser\Presentation\Http\Requests\UpsertSystemUserRequest;
use Modules\SystemUser\Presentation\Http\Resources\SystemUserResource;

final class SystemUserController extends Controller
{
    public function __construct(
        private readonly ListSystemUsersServiceInterface $listSystemUsers,
        private readonly GetSystemUserServiceInterface $getSystemUser,
        private readonly CreateSystemUserServiceInterface $createSystemUser,
        private readonly UpdateSystemUserServiceInterface $updateSystemUser,
        private readonly DeleteSystemUserServiceInterface $deleteSystemUser,
    ) {
    }

    public function index(ListSystemUserRequest $request): JsonResponse
    {
        $result = $this->listSystemUsers->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => SystemUserResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $systemUser): JsonResponse|SystemUserResource
    {
        $result = $this->getSystemUser->execute($systemUser);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new SystemUserResource($result->valueOrFail());
    }

    public function store(UpsertSystemUserRequest $request): JsonResponse|SystemUserResource
    {
        $result = $this->createSystemUser->execute($request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'SYSTEM_USER_CONFLICT' ? 409 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return (new SystemUserResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertSystemUserRequest $request, int|string $systemUser): JsonResponse|SystemUserResource
    {
        $result = $this->updateSystemUser->execute($systemUser, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = match ($error->code) {
                'SYSTEM_USER_NOT_FOUND' => 404,
                'SYSTEM_USER_CONFLICT' => 409,
                default => 422,
            };

            return response()->json(['message' => $error->message], $status);
        }

        return new SystemUserResource($result->valueOrFail());
    }

    public function destroy(int|string $systemUser): JsonResponse
    {
        $result = $this->deleteSystemUser->execute($systemUser);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
