<?php

namespace Modules\Logistics\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Logistics\Application\UseCases\CreateCarrierUseCase;
use Modules\Logistics\Infrastructure\Repositories\CarrierRepository;
use Modules\Logistics\Presentation\Requests\StoreCarrierRequest;
use Modules\Shared\Application\ResponseFormatter;

class CarrierController extends Controller
{
    public function __construct(
        private CreateCarrierUseCase $createUseCase,
        private CarrierRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreCarrierRequest $request): JsonResponse
    {
        $carrier = $this->createUseCase->execute($request->validated());
        return ResponseFormatter::success($carrier, 'Carrier created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $carrier = $this->repo->findById($id);
        if (! $carrier) {
            return ResponseFormatter::error('Carrier not found.', [], 404);
        }
        return ResponseFormatter::success($carrier);
    }

    public function update(StoreCarrierRequest $request, string $id): JsonResponse
    {
        $carrier = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($carrier, 'Carrier updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Carrier deleted.');
    }
}
