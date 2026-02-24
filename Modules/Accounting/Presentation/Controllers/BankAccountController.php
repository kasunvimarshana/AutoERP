<?php

namespace Modules\Accounting\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\UseCases\CreateBankAccountUseCase;
use Modules\Accounting\Infrastructure\Repositories\BankAccountRepository;
use Modules\Accounting\Presentation\Requests\StoreBankAccountRequest;
use Modules\Shared\Application\ResponseFormatter;

class BankAccountController extends Controller
{
    public function __construct(
        private CreateBankAccountUseCase $createUseCase,
        private BankAccountRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        try {
            $account = $this->createUseCase->execute($request->validated());
            return ResponseFormatter::success($account, 'Bank account created.', 201);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $account = $this->repo->findById($id);
        if (! $account) {
            return ResponseFormatter::error('Bank account not found.', [], 404);
        }
        return ResponseFormatter::success($account);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Bank account deleted.');
    }
}
