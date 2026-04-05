<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\BankAccountServiceInterface;

class BankAccountController extends Controller
{
    public function __construct(private readonly BankAccountServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int)$request->query('tenant_id', 1);
        return response()->json($this->service->findByTenant($tenantId));
    }

    public function store(Request $request): JsonResponse
    {
        $account = $this->service->create($request->all());
        return response()->json($account, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->service->findById($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return response()->json($this->service->update($id, $request->all()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
