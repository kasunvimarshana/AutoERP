<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Application\Contracts\UseCases\UserServiceInterface;
use Modules\User\Presentation\Http\Requests\ListUserEntityRequest;
use Modules\User\Presentation\Http\Requests\UpsertUserRequest;
use Modules\User\Presentation\Http\Resources\UserRecordResource;

final class UserController extends AbstractUserCrudController
{
    public function __construct(private readonly UserServiceInterface $service)
    {
    }

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $user): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->service->get($user));
    }

    public function store(UpsertUserRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->service->create($request->validated()));
    }

    public function update(UpsertUserRequest $request, int|string $user): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->update($user, $request->validated()));
    }

    public function destroy(int|string $user): JsonResponse
    {
        return $this->responseForDelete($this->service->delete($user));
    }
}
