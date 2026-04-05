<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Application\Contracts\EmployeeServiceInterface;
use Modules\HR\Infrastructure\Http\Resources\EmployeeResource;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeServiceInterface $service) {}

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
        return response()->json(new EmployeeResource($this->service->findById($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $employee = $this->service->create($request->all());
        return response()->json(new EmployeeResource($employee), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $employee = $this->service->update($id, $request->all());
        return response()->json(new EmployeeResource($employee));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function terminate(Request $request, int $id): JsonResponse
    {
        $terminationDate = $request->get('termination_date', date('Y-m-d'));
        $employee = $this->service->terminate($id, $terminationDate);
        return response()->json(new EmployeeResource($employee));
    }

    public function byDepartment(Request $request, int $departmentId): JsonResponse
    {
        $result = $this->service->findByDepartment(
            $departmentId,
            (int) $request->get('per_page', 15),
            (int) $request->get('page', 1)
        );
        return response()->json($result);
    }
}
