<?php

namespace Modules\HR\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\HR\Application\UseCases\CreatePayrollRunUseCase;
use Modules\HR\Application\UseCases\ProcessPayrollRunUseCase;
use Modules\HR\Infrastructure\Repositories\PayrollRunRepository;
use Modules\HR\Presentation\Requests\StorePayrollRunRequest;
use Modules\Shared\Application\ResponseFormatter;

class PayrollRunController extends Controller
{
    public function __construct(
        private CreatePayrollRunUseCase  $createUseCase,
        private ProcessPayrollRunUseCase $processUseCase,
        private PayrollRunRepository     $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StorePayrollRunRequest $request): JsonResponse
    {
        $run = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($run, 'Payroll run created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $run = $this->repo->findById($id);
        if (! $run) {
            return ResponseFormatter::error('Payroll run not found.', [], 404);
        }
        return ResponseFormatter::success($run);
    }

    public function process(string $id): JsonResponse
    {
        try {
            $run = $this->processUseCase->execute($id);
            return ResponseFormatter::success($run, 'Payroll run processed.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }
}
