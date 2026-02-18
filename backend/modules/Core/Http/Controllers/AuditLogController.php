<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\AuditLogResource;
use Modules\Core\Models\AuditLog;
use Modules\Core\Services\TenantContext;

class AuditLogController extends BaseController
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * @OA\Get(
     *     path="/api/audit-logs",
     *     operationId="getAuditLogs",
     *     tags={"Audit Logs"},
     *     summary="Get audit logs",
     *     description="Retrieve paginated list of audit logs with optional filtering by user, event type, and auditable model. Automatically filtered by tenant context if available.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="event",
     *         in="query",
     *         description="Filter by event type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created", "updated", "deleted", "restored", "viewed"}, example="created")
     *     ),
     *     @OA\Parameter(
     *         name="auditable_type",
     *         in="query",
     *         description="Filter by auditable model type (full class name)",
     *         required=false,
     *         @OA\Schema(type="string", example="Modules\\Inventory\\Models\\Product")
     *     ),
     *     @OA\Parameter(
     *         name="auditable_id",
     *         in="query",
     *         description="Filter by auditable model ID (use with auditable_type)",
     *         required=false,
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=250),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/AuditLogResource")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function index(Request $request)
    {
        $query = AuditLog::query();

        if ($this->tenantContext->hasTenant()) {
            $query->forTenant($this->tenantContext->getTenantId());
        }

        if ($request->user_id) {
            $query->forUser($request->user_id);
        }

        if ($request->event) {
            $query->forEvent($request->event);
        }

        if ($request->auditable_type) {
            $query->forModel($request->auditable_type, $request->auditable_id);
        }

        $logs = $query->with(['user', 'auditable'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->success(AuditLogResource::collection($logs));
    }

    /**
     * @OA\Get(
     *     path="/api/audit-logs/{id}",
     *     operationId="getAuditLogById",
     *     tags={"Audit Logs"},
     *     summary="Get audit log details",
     *     description="Retrieve detailed information about a specific audit log entry. Access is restricted by tenant context.",
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Audit log ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1234)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", ref="#/components/schemas/AuditLogResource")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Access denied (tenant mismatch)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Audit log not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(int $id)
    {
        $log = AuditLog::with(['user', 'auditable'])->find($id);

        if (! $log) {
            return $this->notFound('Audit log not found');
        }

        if ($this->tenantContext->hasTenant() && $log->tenant_id !== $this->tenantContext->getTenantId()) {
            return $this->forbidden('Access denied');
        }

        return $this->success(AuditLogResource::make($log));
    }
}
