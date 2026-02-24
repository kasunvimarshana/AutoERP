<?php

namespace Modules\AssetManagement\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\AssetManagement\Application\UseCases\DisposeAssetUseCase;
use Modules\AssetManagement\Application\UseCases\RecordDepreciationUseCase;
use Modules\AssetManagement\Application\UseCases\RegisterAssetUseCase;
use Modules\AssetManagement\Domain\Contracts\AssetRepositoryInterface;
use Modules\AssetManagement\Presentation\Requests\DisposeAssetRequest;
use Modules\AssetManagement\Presentation\Requests\RecordDepreciationRequest;
use Modules\AssetManagement\Presentation\Requests\StoreAssetRequest;

class AssetController extends Controller
{
    public function __construct(
        private AssetRepositoryInterface  $assetRepo,
        private RegisterAssetUseCase      $registerUseCase,
        private DisposeAssetUseCase       $disposeUseCase,
        private RecordDepreciationUseCase $depreciationUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->assetRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $asset = $this->registerUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($asset, 201);
    }

    public function show(string $id): JsonResponse
    {
        $asset = $this->assetRepo->findById($id);

        if (! $asset) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($asset);
    }

    public function update(StoreAssetRequest $request, string $id): JsonResponse
    {
        $asset = $this->assetRepo->update($id, $request->validated());

        return response()->json($asset);
    }

    public function dispose(DisposeAssetRequest $request, string $id): JsonResponse
    {
        $asset = $this->disposeUseCase->execute($id, $request->validated());

        return response()->json($asset);
    }

    public function depreciate(RecordDepreciationRequest $request, string $id): JsonResponse
    {
        $asset = $this->depreciationUseCase->execute($id, $request->validated());

        return response()->json($asset);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->assetRepo->delete($id);

        return response()->json(null, 204);
    }
}
