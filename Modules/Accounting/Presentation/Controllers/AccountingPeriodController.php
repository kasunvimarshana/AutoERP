<?php

namespace Modules\Accounting\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\UseCases\CloseAccountingPeriodUseCase;
use Modules\Accounting\Application\UseCases\CreateAccountingPeriodUseCase;
use Modules\Accounting\Application\UseCases\LockAccountingPeriodUseCase;
use Modules\Accounting\Infrastructure\Repositories\AccountingPeriodRepository;
use Modules\Accounting\Presentation\Requests\StoreAccountingPeriodRequest;
use Modules\Shared\Application\ResponseFormatter;

class AccountingPeriodController extends Controller
{
    public function __construct(
        private CreateAccountingPeriodUseCase $createUseCase,
        private CloseAccountingPeriodUseCase  $closeUseCase,
        private LockAccountingPeriodUseCase   $lockUseCase,
        private AccountingPeriodRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id ?? request('tenant_id');
        return ResponseFormatter::paginated($this->repo->paginate($tenantId, 15));
    }

    public function store(StoreAccountingPeriodRequest $request): JsonResponse
    {
        try {
            $period = $this->createUseCase->execute(array_merge(
                $request->validated(),
                ['tenant_id' => auth()->user()?->tenant_id ?? $request->input('tenant_id')],
            ));
            return ResponseFormatter::success($period, 'Accounting period created.', 201);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $period = $this->repo->findById($id);
        if (! $period) {
            return ResponseFormatter::error('Accounting period not found.', [], 404);
        }
        return ResponseFormatter::success($period);
    }

    public function close(string $id): JsonResponse
    {
        try {
            $period = $this->closeUseCase->execute([
                'id'        => $id,
                'closed_by' => auth()->id(),
            ]);
            return ResponseFormatter::success($period, 'Accounting period closed.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function lock(string $id): JsonResponse
    {
        try {
            $period = $this->lockUseCase->execute([
                'id'        => $id,
                'locked_by' => auth()->id(),
            ]);
            return ResponseFormatter::success($period, 'Accounting period locked.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }
}
