<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Controllers\Platform;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Audit\Http\Requests\Platform\ListPlatformAuditLogRequest;
use Modules\Audit\Http\Requests\Platform\ViewPlatformAuditLogRequest;
use Modules\Audit\Http\Resources\AuditLogDetailResource;
use Modules\Audit\Http\Resources\AuditLogSummaryResource;
use Modules\Audit\Models\AuditLog;
use Modules\Audit\Services\AuditQueryFactory;
use Modules\Audit\Services\Platform\GetPlatformAuditLog;
use Modules\Audit\Services\Platform\ListPlatformAuditLogs;
use Modules\Audit\Services\Platform\PlatformAuditAuthorizationService;

final class PlatformAuditLogController extends Controller
{
    public function __construct(
        private readonly ListPlatformAuditLogs $list,
        private readonly GetPlatformAuditLog $get,
        private readonly AuditQueryFactory $queryFactory,
        private readonly PlatformAuditAuthorizationService $authorization,
    ) {}

    public function index(ListPlatformAuditLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $page = $this->list->execute(
            $this->queryFactory->fromValidated($validated),
            isset($validated['tenant_id']) ? (int) $validated['tenant_id'] : null,
        );

        return response()->json([
            'data' => AuditLogSummaryResource::collection($page->items)->resolve($request),
            'meta' => [
                'next_cursor' => $page->nextCursor,
                'has_more' => $page->nextCursor !== null,
                'per_page' => $page->perPage,
            ],
        ]);
    }

    public function show(ViewPlatformAuditLogRequest $request, int $id): AuditLogDetailResource
    {
        $record = $this->get->execute($id);
        if ($record === null) {
            throw (new ModelNotFoundException())->setModel(AuditLog::class, [$id]);
        }

        return new AuditLogDetailResource($record, $this->authorization->canViewSensitive());
    }
}
