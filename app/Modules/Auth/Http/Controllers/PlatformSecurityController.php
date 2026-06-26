<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\Platform\ListPlatformSessionsRequest;
use Modules\Auth\Http\Requests\Platform\PlatformSecurityActionRequest;
use Modules\Auth\Services\PlatformSessionService;

final class PlatformSecurityController extends Controller
{
    public function __construct(private readonly PlatformSessionService $sessions) {}

    public function sessions(ListPlatformSessionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        return $this->noStore(response()->json($this->sessions->page(
            isset($validated['operator_id']) ? (int) $validated['operator_id'] : null,
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 20),
        )));
    }

    public function revoke(PlatformSecurityActionRequest $request, string $session): JsonResponse
    {
        return $this->noStore(response()->json([
            'data' => $this->sessions->revoke($session, (string) $request->validated('reason')),
        ]));
    }

    public function revokeOperatorSessions(PlatformSecurityActionRequest $request, int $operator): JsonResponse
    {
        return $this->noStore(response()->json([
            'revoked_count' => $this->sessions->revokeAllForOperator(
                $operator,
                (string) $request->validated('reason'),
            ),
        ]));
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        return $response;
    }
}
