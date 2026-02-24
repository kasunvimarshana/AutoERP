<?php

namespace Modules\Tax\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Tax\Application\UseCases\CreateTaxRateUseCase;
use Modules\Tax\Application\UseCases\DeactivateTaxRateUseCase;
use Modules\Tax\Domain\Contracts\TaxRateRepositoryInterface;
use Modules\Tax\Presentation\Requests\StoreTaxRateRequest;

class TaxRateController extends Controller
{
    public function __construct(
        private TaxRateRepositoryInterface $taxRateRepo,
        private CreateTaxRateUseCase       $createUseCase,
        private DeactivateTaxRateUseCase   $deactivateUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->taxRateRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function active(): JsonResponse
    {
        return response()->json($this->taxRateRepo->findActiveByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreTaxRateRequest $request): JsonResponse
    {
        $taxRate = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($taxRate, 201);
    }

    public function show(string $id): JsonResponse
    {
        $taxRate = $this->taxRateRepo->findById($id);

        if (! $taxRate) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($taxRate);
    }

    public function update(StoreTaxRateRequest $request, string $id): JsonResponse
    {
        $taxRate = $this->taxRateRepo->update($id, $request->validated());

        return response()->json($taxRate);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->taxRateRepo->delete($id);

        return response()->json(null, 204);
    }

    public function deactivate(string $id): JsonResponse
    {
        $taxRate = $this->deactivateUseCase->execute($id);

        return response()->json($taxRate);
    }
}
