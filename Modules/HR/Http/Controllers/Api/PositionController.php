<?php

namespace Modules\HR\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HR\Http\Requests\StorePositionRequest;
use Modules\HR\Http\Requests\UpdatePositionRequest;
use Modules\HR\Http\Resources\EmployeeResource;
use Modules\HR\Http\Resources\PositionResource;
use Modules\HR\Repositories\Contracts\EmployeeRepositoryInterface;
use Modules\HR\Repositories\Contracts\PositionRepositoryInterface;

class PositionController extends Controller
{
    public function __construct(
        protected PositionRepositoryInterface $positionRepository,
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $positions = $this->positionRepository->paginate($perPage);

        return PositionResource::collection($positions)->response();
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = $this->positionRepository->create($request->validated());

        return (new PositionResource($position))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $position = $this->positionRepository->find($id);
        if (! $position) {
            return response()->json(['message' => 'Position not found'], 404);
        }

        return (new PositionResource($position))->response();
    }

    public function update(UpdatePositionRequest $request, int $id): JsonResponse
    {
        $position = $this->positionRepository->update($id, $request->validated());

        return (new PositionResource($position))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->positionRepository->delete($id);
        if (! $deleted) {
            return response()->json(['message' => 'Position not found'], 404);
        }

        return response()->json(['message' => 'Position deleted successfully'], 200);
    }

    public function employees(int $id): JsonResponse
    {
        $employees = $this->employeeRepository->getByPosition($id);

        return EmployeeResource::collection($employees)->response();
    }
}
