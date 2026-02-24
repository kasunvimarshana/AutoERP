<?php

namespace Modules\Reporting\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Reporting\Application\UseCases\CreateDashboardUseCase;
use Modules\Reporting\Domain\Contracts\DashboardRepositoryInterface;
use Modules\Reporting\Presentation\Requests\StoreDashboardRequest;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardRepositoryInterface $dashboardRepo,
        private CreateDashboardUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->dashboardRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreDashboardRequest $request): JsonResponse
    {
        $dashboard = $this->createUseCase->execute(array_merge(
            $request->validated(),
            [
                'tenant_id' => auth()->user()?->tenant_id,
                'user_id'   => auth()->id(),
            ]
        ));

        return response()->json($dashboard, 201);
    }

    public function show(string $id): JsonResponse
    {
        $dashboard = $this->dashboardRepo->findById($id);

        if (! $dashboard) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($dashboard);
    }

    public function update(StoreDashboardRequest $request, string $id): JsonResponse
    {
        $dashboard = $this->dashboardRepo->update($id, $request->validated());

        return response()->json($dashboard);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->dashboardRepo->delete($id);

        return response()->json(null, 204);
    }
}
