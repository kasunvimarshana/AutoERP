<?php
namespace Modules\Accounting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\CreateAccountServiceInterface;
use Modules\Accounting\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Accounting\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Accounting\Application\DTOs\AccountData;
use Modules\Accounting\Domain\Repositories\AccountRepositoryInterface;
use Modules\Accounting\Infrastructure\Http\Resources\AccountResource;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\AccountModel;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly CreateAccountServiceInterface $createAccountService,
        private readonly UpdateAccountServiceInterface $updateAccountService,
        private readonly DeleteAccountServiceInterface $deleteAccountService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $filters  = $request->only(['type', 'is_active', 'parent_id']);
        $perPage  = (int) $request->query('per_page', 15);

        $paginator = $this->accountRepository->findAll($tenantId, $filters, $perPage);

        return response()->json([
            'data' => AccountResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'   => 'required|integer',
            'code'        => 'required|string|max:20',
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|in:asset,liability,equity,revenue,expense',
            'parent_id'   => 'nullable|integer',
            'currency'    => 'nullable|string|size:3',
            'is_active'   => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $data = new AccountData(
            tenantId:    $validated['tenant_id'],
            code:        $validated['code'],
            name:        $validated['name'],
            type:        $validated['type'],
            parentId:    $validated['parent_id']   ?? null,
            currency:    $validated['currency']    ?? 'USD',
            isActive:    $validated['is_active']   ?? true,
            description: $validated['description'] ?? null,
        );

        $account = $this->createAccountService->execute($data);

        $model = AccountModel::find($account->id);

        return response()->json(new AccountResource($model), 201);
    }

    public function show(int $id): JsonResponse
    {
        $model = AccountModel::find($id);

        if ($model === null) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        return response()->json(new AccountResource($model));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $account = $this->accountRepository->findById($id);

        if ($account === null) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $validated = $request->validate([
            'code'        => 'sometimes|string|max:20',
            'name'        => 'sometimes|string|max:255',
            'type'        => 'sometimes|string|in:asset,liability,equity,revenue,expense',
            'parent_id'   => 'nullable|integer',
            'currency'    => 'nullable|string|size:3',
            'is_active'   => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $updated = $this->updateAccountService->execute($account, $validated);

        $model = AccountModel::find($updated->id);

        return response()->json(new AccountResource($model));
    }

    public function destroy(int $id): JsonResponse
    {
        $account = $this->accountRepository->findById($id);

        if ($account === null) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $this->deleteAccountService->execute($account);

        return response()->json(['message' => 'Account deleted.']);
    }
}
