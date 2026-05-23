<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Application\Services\UserService;
use Modules\User\Domain\Exceptions\UserRecordNotFoundException;
use Modules\User\Presentation\Http\Controllers\Concerns\HandlesUserHttp;
use Modules\User\Presentation\Http\Requests\StoreUserRequest;
use Modules\User\Presentation\Http\Requests\UpdateUserRequest;
use Modules\User\Presentation\Http\Resources\UserResource;

class UserController extends Controller
{
    use HandlesUserHttp;

    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): mixed
    {
        return UserResource::collection($this->users->listUsers(
            $this->filters($request, ['tenant_id', 'organization_unit_id', 'email', 'status']),
            $this->perPage($request),
        ));
    }

    public function store(StoreUserRequest $request): UserResource|JsonResponse
    {
        try {
            $record = $this->users->createUser(UserData::fromArray($request->validated()));

            return (new UserResource($record))->response()->setStatusCode(201);
        } catch (InvalidArgumentException $exception) {
            return $this->invalid($exception);
        }
    }

    public function show(int|string $user): UserResource|JsonResponse
    {
        try {
            return new UserResource($this->users->findUser($user));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateUserRequest $request, int|string $user): UserResource|JsonResponse
    {
        try {
            return new UserResource($this->users->updateUser($user, UserData::fromArray($request->validated())));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        } catch (InvalidArgumentException $exception) {
            return $this->invalid($exception);
        }
    }

    public function destroy(int|string $user): JsonResponse
    {
        try {
            $this->users->deleteUser($user);

            return response()->json(null, 204);
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
