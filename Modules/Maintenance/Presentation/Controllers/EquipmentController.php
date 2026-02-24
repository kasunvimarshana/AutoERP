<?php

namespace Modules\Maintenance\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Maintenance\Application\UseCases\DecommissionEquipmentUseCase;
use Modules\Maintenance\Application\UseCases\RegisterEquipmentUseCase;
use Modules\Maintenance\Domain\Contracts\EquipmentRepositoryInterface;
use Modules\Maintenance\Presentation\Requests\StoreEquipmentRequest;

class EquipmentController extends Controller
{
    public function __construct(
        private EquipmentRepositoryInterface  $equipmentRepo,
        private RegisterEquipmentUseCase      $registerUseCase,
        private DecommissionEquipmentUseCase  $decommissionUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->equipmentRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        $equipment = $this->registerUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($equipment, 201);
    }

    public function show(string $id): JsonResponse
    {
        $equipment = $this->equipmentRepo->findById($id);

        if (! $equipment) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($equipment);
    }

    public function update(StoreEquipmentRequest $request, string $id): JsonResponse
    {
        $equipment = $this->equipmentRepo->update($id, $request->validated());

        return response()->json($equipment);
    }

    public function decommission(string $id): JsonResponse
    {
        $equipment = $this->decommissionUseCase->execute($id);

        return response()->json($equipment);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->equipmentRepo->delete($id);

        return response()->json(null, 204);
    }
}
