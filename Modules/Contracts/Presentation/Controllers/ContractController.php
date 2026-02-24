<?php

namespace Modules\Contracts\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Contracts\Application\UseCases\ActivateContractUseCase;
use Modules\Contracts\Application\UseCases\CreateContractUseCase;
use Modules\Contracts\Application\UseCases\TerminateContractUseCase;
use Modules\Contracts\Domain\Contracts\ContractRepositoryInterface;
use Modules\Contracts\Presentation\Requests\StoreContractRequest;
use Modules\Contracts\Presentation\Requests\TerminateContractRequest;

class ContractController extends Controller
{
    public function __construct(
        private ContractRepositoryInterface $contractRepo,
        private CreateContractUseCase       $createUseCase,
        private ActivateContractUseCase     $activateUseCase,
        private TerminateContractUseCase    $terminateUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->contractRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        $contract = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($contract, 201);
    }

    public function show(string $id): JsonResponse
    {
        $contract = $this->contractRepo->findById($id);

        if (! $contract) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($contract);
    }

    public function update(StoreContractRequest $request, string $id): JsonResponse
    {
        $contract = $this->contractRepo->update($id, $request->validated());

        return response()->json($contract);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->contractRepo->delete($id);

        return response()->json(null, 204);
    }

    public function activate(string $id): JsonResponse
    {
        $contract = $this->activateUseCase->execute($id);

        return response()->json($contract);
    }

    public function terminate(TerminateContractRequest $request, string $id): JsonResponse
    {
        $contract = $this->terminateUseCase->execute($id, $request->validated('reason'));

        return response()->json($contract);
    }
}
