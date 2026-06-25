<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers\Platform;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\User\Constants\UserStatus;
use Modules\User\Http\Requests\Platform\ChangePlatformOperatorStatusRequest;
use Modules\User\Http\Requests\Platform\CreatePlatformOperatorRequest;
use Modules\User\Http\Requests\Platform\ListPlatformOperatorRequest;
use Modules\User\Http\Requests\Platform\ResendPlatformOperatorInvitationRequest;
use Modules\User\Http\Requests\Platform\RevokePlatformOperatorInvitationRequest;
use Modules\User\Http\Requests\Platform\UpdatePlatformOperatorPermissionsRequest;
use Modules\User\Http\Resources\Platform\PlatformOperatorResource;
use Modules\User\Services\Platform\PlatformOperatorService;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationService;

final class PlatformOperatorController extends Controller
{
    public function __construct(
        private readonly PlatformOperatorService $operators,
        private readonly PlatformOperatorInvitationService $invitations,
    ) {}

    public function index(ListPlatformOperatorRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $page = $this->operators->page(
            isset($validated['search']) ? (string) $validated['search'] : null,
            isset($validated['status']) ? (string) $validated['status'] : null,
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 20),
        );

        return response()->json([
            'data' => PlatformOperatorResource::collection($page->items())->resolve($request),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => max(1, $page->lastPage()),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
            'available_permissions' => $this->operators->availablePermissions(),
        ]);
    }

    public function show(int $operator): PlatformOperatorResource
    {
        return new PlatformOperatorResource($this->operators->find($operator));
    }

    public function store(CreatePlatformOperatorRequest $request): JsonResponse
    {
        return (new PlatformOperatorResource($this->operators->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function permissions(
        UpdatePlatformOperatorPermissionsRequest $request,
        int $operator,
    ): PlatformOperatorResource {
        return new PlatformOperatorResource($this->operators->synchronizePermissions(
            $operator,
            (int) $request->validated('expected_version'),
            (array) $request->validated('permissions'),
        ));
    }

    public function activate(ChangePlatformOperatorStatusRequest $request, int $operator): PlatformOperatorResource
    {
        return new PlatformOperatorResource($this->operators->changeStatus(
            $operator,
            (int) $request->validated('expected_version'),
            UserStatus::ACTIVE,
            (string) $request->validated('reason'),
        ));
    }

    public function deactivate(ChangePlatformOperatorStatusRequest $request, int $operator): PlatformOperatorResource
    {
        return new PlatformOperatorResource($this->operators->changeStatus(
            $operator,
            (int) $request->validated('expected_version'),
            UserStatus::INACTIVE,
            (string) $request->validated('reason'),
        ));
    }

    public function resendInvitation(
        ResendPlatformOperatorInvitationRequest $request,
        int $operator,
    ): PlatformOperatorResource {
        return new PlatformOperatorResource($this->invitations->resend(
            $operator,
            (int) $request->validated('expected_version'),
        ));
    }

    public function revokeInvitation(
        RevokePlatformOperatorInvitationRequest $request,
        int $operator,
    ): PlatformOperatorResource {
        return new PlatformOperatorResource($this->invitations->revoke(
            $operator,
            (int) $request->validated('expected_version'),
            (string) $request->validated('reason'),
        ));
    }
}
