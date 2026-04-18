<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Audit\Application\Contracts\AuditServiceInterface;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Infrastructure\Http\Requests\ListAuditLogRequest;
use Modules\Audit\Infrastructure\Http\Resources\AuditLogCollection;
use Modules\Audit\Infrastructure\Http\Resources\AuditLogResource;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuditLogController extends AuthorizedController
{
    public function __construct(private readonly AuditServiceInterface $auditService) {}

    public function index(ListAuditLogRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'event' => $validated['event'] ?? null,
            'auditable_type' => $validated['auditable_type'] ?? null,
            'auditable_id' => $validated['auditable_id'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $sort = $validated['sort'] ?? null;

        $logs = $this->auditService->list($filters, $perPage, $page, $sort);

        return (new AuditLogCollection($logs))->response();
    }

    public function show(int $auditLogId): AuditLogResource
    {
        $auditLog = $this->auditService->find($auditLogId);
        if (! $auditLog) {
            throw new NotFoundHttpException('Audit log not found.');
        }

        $this->authorize('view', $auditLog);

        return new AuditLogResource($auditLog);
    }
}
