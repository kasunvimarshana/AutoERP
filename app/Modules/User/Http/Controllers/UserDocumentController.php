<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\ListUserEntityRequest;
use Modules\User\Http\Requests\UpsertUserDocumentRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\UserDocumentService;

final class UserDocumentController extends AbstractUserCrudController
{
    public function __construct(private readonly UserDocumentService $service) {}

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $userDocument): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->service->get($userDocument));
    }

    public function store(UpsertUserDocumentRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->service->create($request->validated()));
    }

    public function update(UpsertUserDocumentRequest $request, int|string $userDocument): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->update($userDocument, $request->validated()));
    }

    public function destroy(int|string $userDocument): JsonResponse
    {
        return $this->responseForDelete($this->service->delete($userDocument));
    }
}
