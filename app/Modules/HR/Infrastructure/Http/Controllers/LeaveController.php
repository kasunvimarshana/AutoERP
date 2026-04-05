<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Application\Contracts\LeaveRequestServiceInterface;
use Modules\HR\Infrastructure\Http\Resources\LeaveRequestResource;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveRequestServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $result = $this->service->findByTenant(
            $tenantId,
            (int) $request->get('per_page', 15),
            (int) $request->get('page', 1)
        );
        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(new LeaveRequestResource($this->service->findById($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $request_entity = $this->service->create($request->all());
        return response()->json(new LeaveRequestResource($request_entity), 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $approverId = (int) $request->get('approver_id', 0);
        $req = $this->service->approve($id, $approverId);
        return response()->json(new LeaveRequestResource($req));
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $approverId = (int) $request->get('approver_id', 0);
        $reason = (string) $request->get('reason', '');
        $req = $this->service->reject($id, $approverId, $reason);
        return response()->json(new LeaveRequestResource($req));
    }

    public function cancel(int $id): JsonResponse
    {
        $req = $this->service->cancel($id);
        return response()->json(new LeaveRequestResource($req));
    }

    public function byEmployee(Request $request, int $employeeId): JsonResponse
    {
        $result = $this->service->findByEmployee(
            $employeeId,
            (int) $request->get('per_page', 15),
            (int) $request->get('page', 1)
        );
        return response()->json($result);
    }

    public function pending(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $pending = $this->service->findPendingByTenant($tenantId);
        return response()->json(array_map(fn($r) => new LeaveRequestResource($r), $pending));
    }
}
