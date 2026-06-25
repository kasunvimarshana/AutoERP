<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers\Platform;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\User\Http\Requests\Platform\AcceptPlatformOperatorInvitationRequest;
use Modules\User\Http\Requests\Platform\InspectPlatformOperatorInvitationRequest;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationService;

final class PlatformOperatorInvitationController extends Controller
{
    public function __construct(private readonly PlatformOperatorInvitationService $invitations) {}

    public function inspect(InspectPlatformOperatorInvitationRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->invitations->inspect((string) $request->validated('token')),
        ]);
    }

    public function accept(AcceptPlatformOperatorInvitationRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->invitations->accept(
                (string) $request->validated('token'),
                (string) $request->validated('password'),
            ),
        ]);
    }
}
