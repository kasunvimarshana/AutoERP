<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Application\Contracts\PayrollServiceInterface;
use Modules\HR\Infrastructure\Http\Resources\PayrollResource;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollServiceInterface $service) {}

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
        return response()->json(new PayrollResource($this->service->findById($id)));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function process(Request $request): JsonResponse
    {
        $record = $this->service->processPayroll(
            (int) $request->get('employee_id', 0),
            (int) $request->get('year', date('Y')),
            (int) $request->get('month', date('n')),
            (int) $request->get('processed_by_id', 0),
            $request->get('overrides', [])
        );
        return response()->json(new PayrollResource($record), 201);
    }

    public function approve(int $id): JsonResponse
    {
        $record = $this->service->approve($id);
        return response()->json(new PayrollResource($record));
    }

    public function markAsPaid(Request $request, int $id): JsonResponse
    {
        $paymentDate = (string) $request->get('payment_date', date('Y-m-d'));
        $reference   = (string) $request->get('payment_reference', '');
        $record = $this->service->markAsPaid($id, $paymentDate, $reference);
        return response()->json(new PayrollResource($record));
    }

    public function cancel(int $id): JsonResponse
    {
        $record = $this->service->cancel($id);
        return response()->json(new PayrollResource($record));
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

    public function byPeriod(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $year     = (int) $request->get('year', date('Y'));
        $month    = (int) $request->get('month', date('n'));
        $records  = $this->service->findByTenantAndPeriod($tenantId, $year, $month);
        return response()->json(array_map(fn($r) => new PayrollResource($r), $records));
    }
}
