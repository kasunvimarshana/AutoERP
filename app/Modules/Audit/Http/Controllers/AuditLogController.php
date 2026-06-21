<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Audit\Http\Requests\ListAuditLogRequest;
use Modules\Audit\Http\Requests\ViewAuditLogRequest;
use Modules\Audit\Http\Resources\AuditLogDetailResource;
use Modules\Audit\Http\Resources\AuditLogSummaryResource;
use Modules\Audit\Models\AuditLog;
use Modules\Audit\Services\AuditAuthorizationService;
use Modules\Audit\Services\AuditQueryFactory;
use Modules\Audit\Services\GetAuditLog;
use Modules\Audit\Services\ListAuditLogs;

final class AuditLogController extends Controller
{
    public function __construct(
        private readonly ListAuditLogs $list,
        private readonly GetAuditLog $get,
        private readonly AuditQueryFactory $queryFactory,
        private readonly AuditAuthorizationService $authorization,
    ) {}

    public function index(ListAuditLogRequest $request): JsonResponse
    {
        $page = $this->list->execute($this->queryFactory->fromValidated($request->validated()));

        return response()->json([
            'data' => AuditLogSummaryResource::collection($page->items)->resolve($request),
            'meta' => [
                'next_cursor' => $page->nextCursor,
                'has_more' => $page->nextCursor !== null,
                'per_page' => $page->perPage,
            ],
        ]);
    }

    public function show(ViewAuditLogRequest $request, int $id): AuditLogDetailResource
    {
        $record = $this->get->execute($id);
        if ($record === null) {
            throw (new ModelNotFoundException())->setModel(AuditLog::class, [$id]);
        }

        return new AuditLogDetailResource($record, $this->authorization->canViewSensitiveCurrent());
    }
}
