<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\UserAuthorizationService;

abstract class AbstractUserCrudController extends Controller
{
    protected function canUse(string $permission): bool
    {
        return app(UserAuthorizationService::class)->canCurrent($permission);
    }

    protected function forbidden(): JsonResponse
    {
        return response()->json(['message' => 'This action is not authorized.'], 403);
    }

    protected function responseForList(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $value = $result->valueOrFail();

        if ($value instanceof PagedResult) {
            return response()->json([
                'data' => UserRecordResource::collection($value->items)->resolve(),
                'meta' => $value->paginationMeta(),
            ]);
        }

        $items = is_array($value) ? $value : [];

        return response()->json([
            'data' => UserRecordResource::collection($items)->resolve(),
            'meta' => null,
        ]);
    }

    protected function responseForShow(Result $result, string $notFoundCode = 'USER_NOT_FOUND'): JsonResponse|UserRecordResource
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === $notFoundCode ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new UserRecordResource($result->valueOrFail());
    }

    protected function responseForStore(Result $result): JsonResponse|UserRecordResource
    {
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new UserRecordResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    protected function responseForUpdate(Result $result, string $notFoundCode = 'USER_NOT_FOUND'): JsonResponse|UserRecordResource
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === $notFoundCode ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new UserRecordResource($result->valueOrFail());
    }

    protected function responseForDelete(Result $result, string $notFoundCode = 'USER_NOT_FOUND'): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === $notFoundCode ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return response()->json(status: 204);
    }
}
