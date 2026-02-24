<?php

namespace Modules\QualityControl\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\QualityControl\Application\UseCases\CreateQualityAlertUseCase;
use Modules\QualityControl\Domain\Contracts\QualityAlertRepositoryInterface;
use Modules\QualityControl\Presentation\Requests\StoreQualityAlertRequest;

class QualityAlertController extends Controller
{
    public function __construct(
        private QualityAlertRepositoryInterface $alertRepo,
        private CreateQualityAlertUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;
        $items    = $this->alertRepo->findByTenant($tenantId);

        return response()->json($items);
    }

    public function store(StoreQualityAlertRequest $request): JsonResponse
    {
        $alert = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($alert, 201);
    }

    public function show(string $id): JsonResponse
    {
        $alert = $this->alertRepo->findById($id);

        if (! $alert) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($alert);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->alertRepo->delete($id);

        return response()->json(null, 204);
    }
}
