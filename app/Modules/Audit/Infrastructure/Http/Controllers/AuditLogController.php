<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Audit\Application\Contracts\AuditLogServiceInterface;
use Modules\Audit\Infrastructure\Http\Resources\AuditLogResource;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogServiceInterface $auditLogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['event', 'user_id', 'auditable_type', 'from', 'to']);
        $logs = $this->auditLogService->getForTenant($request->user()->tenant_id, $filters);

        return response()->json(AuditLogResource::collection(collect($logs)));
    }

    public function show(string $id): JsonResponse
    {
        $log = $this->auditLogService->getById($id);

        return response()->json(new AuditLogResource($log));
    }

    public function forEntity(Request $request, string $type, string $id): JsonResponse
    {
        $logs = $this->auditLogService->getForEntity($request->user()->tenant_id, $type, $id);

        return response()->json(AuditLogResource::collection(collect($logs)));
    }
}
