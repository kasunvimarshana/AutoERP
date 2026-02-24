<?php

namespace Modules\Recruitment\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Application\UseCases\CreateJobPositionUseCase;
use Modules\Recruitment\Domain\Contracts\JobPositionRepositoryInterface;
use Modules\Recruitment\Presentation\Requests\StoreJobPositionRequest;

class JobPositionController extends Controller
{
    public function __construct(
        private JobPositionRepositoryInterface $positionRepo,
        private CreateJobPositionUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->positionRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreJobPositionRequest $request): JsonResponse
    {
        $position = $this->createUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($position, 201);
    }

    public function show(string $id): JsonResponse
    {
        $position = $this->positionRepo->findById($id);

        if (! $position) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($position);
    }

    public function update(StoreJobPositionRequest $request, string $id): JsonResponse
    {
        $position = $this->positionRepo->update($id, $request->validated());

        return response()->json($position);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->positionRepo->delete($id);

        return response()->json(null, 204);
    }
}
