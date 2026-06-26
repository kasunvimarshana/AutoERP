<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserErrorCode;
use Modules\User\Http\Resources\UserRecordResource;

abstract class AbstractUserCrudController extends Controller
{
    protected function responseForList(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            return $this->errorResponse($result);
        }
        $value = $result->valueOrFail();
        if ($value instanceof PagedResult) {
            return response()->json([
                'data' => UserRecordResource::collection($value->items)->resolve(),
                'meta' => $value->paginationMeta(),
            ]);
        }

        return response()->json([
            'data' => UserRecordResource::collection(is_array($value) ? $value : [])->resolve(),
            'meta' => null,
        ]);
    }

    protected function responseForShow(Result $result): JsonResponse|UserRecordResource
    {
        return $result->isFailure()
            ? $this->errorResponse($result)
            : new UserRecordResource($result->valueOrFail());
    }

    protected function responseForStore(Result $result): JsonResponse|UserRecordResource
    {
        return $result->isFailure()
            ? $this->errorResponse($result)
            : (new UserRecordResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    protected function responseForUpdate(Result $result): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($result);
    }

    protected function responseForDelete(Result $result): JsonResponse
    {
        return $result->isFailure() ? $this->errorResponse($result) : response()->json(status: 204);
    }

    protected function errorResponse(Result $result): JsonResponse
    {
        $error = $result->errorOrFail();
        $status = match ($error->code) {
            UserErrorCode::FORBIDDEN => 403,
            UserErrorCode::NOT_FOUND,
            UserErrorCode::ROLE_NOT_FOUND,
            UserErrorCode::PERMISSION_NOT_FOUND,
            UserErrorCode::ORGANIZATION_UNIT_NOT_FOUND,
            UserErrorCode::ASSIGNMENT_NOT_FOUND => 404,
            UserErrorCode::STALE_RECORD,
            UserErrorCode::CONFLICT,
            UserErrorCode::LAST_ADMIN,
            UserErrorCode::PROTECTED_ACCOUNT => 409,
            UserErrorCode::PLAN_LIMIT_REACHED => 422,
            default => 422,
        };

        return response()->json([
            'message' => $error->message,
            'error' => ['code' => $error->code, 'context' => $error->context],
        ], $status);
    }
}
