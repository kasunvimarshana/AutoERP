<?php

namespace Modules\FieldService\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\FieldService\Domain\Contracts\ServiceTeamRepositoryInterface;
use Modules\FieldService\Presentation\Requests\StoreServiceTeamRequest;

class ServiceTeamController extends Controller
{
    public function __construct(
        private ServiceTeamRepositoryInterface $repo,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->repo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreServiceTeamRequest $request): JsonResponse
    {
        $team = $this->repo->create(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($team, 201);
    }

    public function show(string $id): JsonResponse
    {
        $team = $this->repo->findById($id);

        if (! $team) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($team);
    }

    public function update(StoreServiceTeamRequest $request, string $id): JsonResponse
    {
        return response()->json($this->repo->update($id, $request->validated()));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);

        return response()->json(null, 204);
    }
}
