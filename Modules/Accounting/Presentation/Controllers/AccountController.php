<?php

namespace Modules\Accounting\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\UseCases\CreateAccountUseCase;
use Modules\Accounting\Infrastructure\Repositories\AccountRepository;
use Modules\Accounting\Presentation\Requests\StoreAccountRequest;
use Modules\Shared\Application\ResponseFormatter;

class AccountController extends Controller
{
    public function __construct(
        private CreateAccountUseCase $createUseCase,
        private AccountRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($account, 'Account created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $account = $this->repo->findById($id);
        if (! $account) {
            return ResponseFormatter::error('Account not found.', [], 404);
        }
        return ResponseFormatter::success($account);
    }

    public function update(StoreAccountRequest $request, string $id): JsonResponse
    {
        $account = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($account, 'Account updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Account deleted.');
    }
}
