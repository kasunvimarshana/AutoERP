<?php

namespace Modules\POS\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\POS\Infrastructure\Repositories\PosTerminalRepository;
use Modules\POS\Presentation\Requests\StorePosTerminalRequest;
use Modules\Shared\Application\ResponseFormatter;

class PosTerminalController extends Controller
{
    public function __construct(
        private PosTerminalRepository $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(StorePosTerminalRequest $request): JsonResponse
    {
        $terminal = $this->repo->create($request->validated());
        return ResponseFormatter::success($terminal, 'Terminal created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $terminal = $this->repo->findById($id);
        if (!$terminal) {
            return ResponseFormatter::error('Terminal not found.', [], 404);
        }
        return ResponseFormatter::success($terminal);
    }

    public function update(StorePosTerminalRequest $request, string $id): JsonResponse
    {
        $terminal = $this->repo->update($id, $request->validated());
        return ResponseFormatter::success($terminal, 'Terminal updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Terminal deleted.');
    }
}
