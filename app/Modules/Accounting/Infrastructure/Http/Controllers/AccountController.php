<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\AccountServiceInterface;
use Modules\Accounting\Infrastructure\Http\Resources\AccountResource;
class AccountController extends Controller
{
    public function __construct(
        private readonly AccountServiceInterface $accountService,
    ) {}
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $accounts = $this->accountService->getAllAccounts($tenantId);
        return response()->json(AccountResource::collection($accounts));
    }
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $account  = $this->accountService->createAccount($tenantId, $request->all());
        return response()->json(new AccountResource($account), 201);
    }
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $account  = $this->accountService->getAccount($tenantId, $id);
        return response()->json(new AccountResource($account));
    }
    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $account  = $this->accountService->updateAccount($tenantId, $id, $request->all());
        return response()->json(new AccountResource($account));
    }
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->accountService->deleteAccount($tenantId, $id);
        return response()->json(null, 204);
    }
}
