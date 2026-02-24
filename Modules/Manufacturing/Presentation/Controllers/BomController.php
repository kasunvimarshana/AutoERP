<?php

namespace Modules\Manufacturing\Presentation\Controllers;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Manufacturing\Application\UseCases\CreateBomUseCase;
use Modules\Manufacturing\Infrastructure\Repositories\BomRepository;
use Modules\Manufacturing\Presentation\Requests\StoreBomRequest;
use Modules\Shared\Application\ResponseFormatter;

class BomController extends Controller
{
    public function __construct(
        private CreateBomUseCase $createUseCase,
        private BomRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StoreBomRequest $request): JsonResponse
    {
        try {
            $bom = $this->createUseCase->execute($request->validated());
            return ResponseFormatter::success($bom, 'Bill of Materials created.', 201);
        } catch (DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $bom = $this->repo->findById($id);
        if (! $bom) {
            return ResponseFormatter::error('Bill of Materials not found.', [], 404);
        }
        return ResponseFormatter::success($bom->load('lines'));
    }

    public function update(StoreBomRequest $request, string $id): JsonResponse
    {
        try {
            $bom = $this->repo->update($id, $request->validated());
            return ResponseFormatter::success($bom, 'Bill of Materials updated.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseFormatter::error('Bill of Materials not found.', [], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Bill of Materials deleted.');
    }
}
