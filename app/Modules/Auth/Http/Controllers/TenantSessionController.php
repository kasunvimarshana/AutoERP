<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Requests\ListSessionsRequest;
use Modules\Auth\Http\Requests\RevokeSessionRequest;
use Modules\Auth\Http\Responses\AuthResponseFactory;
use Modules\Auth\Services\TenantSessionService;

final class TenantSessionController extends Controller
{
    public function __construct(
        private readonly TenantSessionService $sessions,
        private readonly AuthResponseFactory $responses,
    ) {}

    public function index(ListSessionsRequest $request): JsonResponse
    {
        $token = $this->tokenPayload($request);

        return $this->responses->success([
            'data' => $this->sessions->listForUser(
                (int) $token['tenant_id'],
                (int) $token['tenant_user_id'],
            ),
        ]);
    }

    public function revoke(RevokeSessionRequest $request, string $session): JsonResponse
    {
        $token = $this->tokenPayload($request);
        $this->sessions->revokeByPublicId(
            (int) $token['tenant_id'],
            (int) $token['tenant_user_id'],
            $session,
            (string) ($request->validated('reason') ?? 'User revoked session.'),
        );

        return $this->responses->success();
    }

    /** @return array<string,mixed> */
    private function tokenPayload(Request $request): array
    {
        $payload = $request->attributes->get('auth_access_token');
        if (! is_array($payload)) {
            throw new AuthFailure(
                AuthErrorCode::UNAUTHORIZED_ACCESS,
                'Authentication is required.',
                401,
            );
        }

        return $payload;
    }
}
