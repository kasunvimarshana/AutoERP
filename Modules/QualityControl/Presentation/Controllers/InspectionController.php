<?php

namespace Modules\QualityControl\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\QualityControl\Application\UseCases\CreateInspectionUseCase;
use Modules\QualityControl\Application\UseCases\FailInspectionUseCase;
use Modules\QualityControl\Application\UseCases\PassInspectionUseCase;
use Modules\QualityControl\Domain\Contracts\InspectionRepositoryInterface;
use Modules\QualityControl\Presentation\Requests\FailInspectionRequest;
use Modules\QualityControl\Presentation\Requests\PassInspectionRequest;
use Modules\QualityControl\Presentation\Requests\StoreInspectionRequest;

class InspectionController extends Controller
{
    public function __construct(
        private InspectionRepositoryInterface $inspectionRepo,
        private CreateInspectionUseCase       $createUseCase,
        private PassInspectionUseCase         $passUseCase,
        private FailInspectionUseCase         $failUseCase,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;
        $items    = $this->inspectionRepo->findByTenant($tenantId);

        return response()->json($items);
    }

    public function store(StoreInspectionRequest $request): JsonResponse
    {
        $inspection = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($inspection, 201);
    }

    public function show(string $id): JsonResponse
    {
        $inspection = $this->inspectionRepo->findById($id);

        if (! $inspection) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($inspection);
    }

    public function pass(PassInspectionRequest $request, string $id): JsonResponse
    {
        $inspection = $this->passUseCase->execute($id, $request->validated());

        return response()->json($inspection);
    }

    public function fail(FailInspectionRequest $request, string $id): JsonResponse
    {
        $inspection = $this->failUseCase->execute($id, $request->validated());

        return response()->json($inspection);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->inspectionRepo->delete($id);

        return response()->json(null, 204);
    }
}
