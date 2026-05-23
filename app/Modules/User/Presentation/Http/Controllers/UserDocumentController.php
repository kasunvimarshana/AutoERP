<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\DTOs\UserDocumentData;
use Modules\User\Application\Services\UserService;
use Modules\User\Domain\Exceptions\UserRecordNotFoundException;
use Modules\User\Presentation\Http\Controllers\Concerns\HandlesUserHttp;
use Modules\User\Presentation\Http\Requests\StoreUserDocumentRequest;
use Modules\User\Presentation\Http\Requests\UpdateUserDocumentRequest;
use Modules\User\Presentation\Http\Resources\UserDocumentResource;

class UserDocumentController extends Controller
{
    use HandlesUserHttp;

    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): mixed
    {
        return UserDocumentResource::collection($this->users->listUserDocuments(
            $this->filters($request, ['tenant_id', 'organization_unit_id', 'user_id', 'name', 'type']),
            $this->perPage($request),
        ));
    }

    public function store(StoreUserDocumentRequest $request): UserDocumentResource
    {
        $record = $this->users->createUserDocument(UserDocumentData::fromArray($request->validated()));

        return new UserDocumentResource($record);
    }

    public function show(int|string $user_document): UserDocumentResource|JsonResponse
    {
        try {
            return new UserDocumentResource($this->users->findUserDocument($user_document));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateUserDocumentRequest $request, int|string $user_document): UserDocumentResource|JsonResponse
    {
        try {
            return new UserDocumentResource($this->users->updateUserDocument($user_document, UserDocumentData::fromArray($request->validated())));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $user_document): JsonResponse
    {
        try {
            $this->users->deleteUserDocument($user_document);

            return response()->json(null, 204);
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
