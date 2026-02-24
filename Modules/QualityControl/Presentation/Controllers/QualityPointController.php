<?php

namespace Modules\QualityControl\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\QualityControl\Domain\Contracts\QualityPointRepositoryInterface;
use Modules\QualityControl\Presentation\Requests\StoreQualityPointRequest;

class QualityPointController extends Controller
{
    public function __construct(
        private QualityPointRepositoryInterface $repo,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id;
        $items    = $this->repo->findByTenant($tenantId);

        return response()->json($items);
    }

    public function store(StoreQualityPointRequest $request): JsonResponse
    {
        $point = $this->repo->create(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($point, 201);
    }

    public function show(string $id): JsonResponse
    {
        $point = $this->repo->findById($id);

        if (! $point) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($point);
    }

    public function update(StoreQualityPointRequest $request, string $id): JsonResponse
    {
        $point = $this->repo->update($id, $request->validated());

        return response()->json($point);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);

        return response()->json(null, 204);
    }
}
