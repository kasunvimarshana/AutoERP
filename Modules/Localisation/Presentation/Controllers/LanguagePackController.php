<?php

namespace Modules\Localisation\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Localisation\Application\UseCases\CreateLanguagePackUseCase;
use Modules\Localisation\Domain\Contracts\LanguagePackRepositoryInterface;
use Modules\Localisation\Presentation\Requests\StoreLanguagePackRequest;

class LanguagePackController extends Controller
{
    public function __construct(
        private LanguagePackRepositoryInterface $languagePackRepo,
        private CreateLanguagePackUseCase       $createUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->languagePackRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreLanguagePackRequest $request): JsonResponse
    {
        $pack = $this->createUseCase->execute(array_merge(
            $request->validated(),
            ['tenant_id' => auth()->user()?->tenant_id]
        ));

        return response()->json($pack, 201);
    }

    public function show(string $id): JsonResponse
    {
        $pack = $this->languagePackRepo->findById($id);

        if (! $pack) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($pack);
    }

    public function update(StoreLanguagePackRequest $request, string $id): JsonResponse
    {
        $pack = $this->languagePackRepo->update($id, $request->validated());

        return response()->json($pack);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->languagePackRepo->delete($id);

        return response()->json(null, 204);
    }
}
