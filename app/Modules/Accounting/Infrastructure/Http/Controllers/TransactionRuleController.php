<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Domain\RepositoryInterfaces\TransactionRuleRepositoryInterface;

class TransactionRuleController extends Controller
{
    public function __construct(private readonly TransactionRuleRepositoryInterface $repo) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int)$request->query('tenant_id', 1);
        return response()->json($this->repo->findActiveByTenant($tenantId));
    }

    public function store(Request $request): JsonResponse
    {
        $rule = $this->repo->create($request->all());
        return response()->json($rule, 201);
    }

    public function show(int $id): JsonResponse
    {
        $rule = $this->repo->findById($id);
        if (!$rule) return response()->json(['message' => 'Not found'], 404);
        return response()->json($rule);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rule = $this->repo->update($id, $request->all());
        if (!$rule) return response()->json(['message' => 'Not found'], 404);
        return response()->json($rule);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->repo->delete($id);
        return response()->json(null, 204);
    }
}
