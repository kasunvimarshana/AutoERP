<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\Platform\ListPlatformSessionsRequest;
use Modules\Auth\Http\Requests\Platform\PlatformSecurityActionRequest;
use Modules\Auth\Http\Resources\PlatformSessionResource;
use Modules\Auth\Services\PlatformSessionService;

final class PlatformSecurityController extends Controller
{
    public function __construct(private readonly PlatformSessionService $sessions) {}

    public function sessions(ListPlatformSessionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $page = $this->sessions->page(
            isset($validated['operator_id']) ? (int) $validated['operator_id'] : null,
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 20),
        );

        return response()->json([
            'data' => PlatformSessionResource::collection($page->items())->resolve($request),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => max(1, $page->lastPage()),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
        ]);
    }

    public function revoke(PlatformSecurityActionRequest $request, string $session): PlatformSessionResource
    {
        return new PlatformSessionResource($this->sessions->revoke(
            $session,
            (string) $request->validated('reason'),
        ));
    }

    public function revokeOperatorSessions(PlatformSecurityActionRequest $request, int $operator): JsonResponse
    {
        return response()->json(['revoked_count' => $this->sessions->revokeAllForOperator(
            $operator,
            (string) $request->validated('reason'),
        )]);
    }

    public function resetMfa(PlatformSecurityActionRequest $request, int $operator): JsonResponse
    {
        $this->sessions->resetMfa($operator, (string) $request->validated('reason'));

        return response()->json(['success' => true]);
    }
}
