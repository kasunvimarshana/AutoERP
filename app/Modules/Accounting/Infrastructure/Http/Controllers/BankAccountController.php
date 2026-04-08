<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\BankAccountServiceInterface;
use Modules\Accounting\Infrastructure\Http\Resources\BankAccountResource;
class BankAccountController extends Controller
{
    public function __construct(
        private readonly BankAccountServiceInterface $bankAccountService,
    ) {}
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $accounts = $this->bankAccountService->getAllBankAccounts($tenantId);
        return response()->json(BankAccountResource::collection($accounts));
    }
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $account  = $this->bankAccountService->createBankAccount($tenantId, $request->all());
        return response()->json(new BankAccountResource($account), 201);
    }
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $account  = $this->bankAccountService->getBankAccount($tenantId, $id);
        return response()->json(new BankAccountResource($account));
    }
    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $account  = $this->bankAccountService->updateBankAccount($tenantId, $id, $request->all());
        return response()->json(new BankAccountResource($account));
    }
    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->bankAccountService->deleteBankAccount($tenantId, $id);
        return response()->json(null, 204);
    }
}
