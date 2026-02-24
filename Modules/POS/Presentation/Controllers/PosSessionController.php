<?php

namespace Modules\POS\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\POS\Application\UseCases\CloseSessionUseCase;
use Modules\POS\Application\UseCases\OpenSessionUseCase;
use Modules\POS\Infrastructure\Repositories\PosSessionRepository;
use Modules\POS\Presentation\Requests\CloseSessionRequest;
use Modules\POS\Presentation\Requests\OpenSessionRequest;
use Modules\Shared\Application\ResponseFormatter;

class PosSessionController extends Controller
{
    public function __construct(
        private OpenSessionUseCase $openUseCase,
        private CloseSessionUseCase $closeUseCase,
        private PosSessionRepository $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->paginate(request()->all(), 15));
    }

    public function store(OpenSessionRequest $request): JsonResponse
    {
        try {
            $session = $this->openUseCase->execute($request->validated());
            return ResponseFormatter::success($session, 'Session opened.', 201);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $session = $this->repo->findById($id);
        if (!$session) {
            return ResponseFormatter::error('Session not found.', [], 404);
        }
        return ResponseFormatter::success($session);
    }

    public function close(CloseSessionRequest $request, string $id): JsonResponse
    {
        try {
            $session = $this->closeUseCase->execute(array_merge(
                $request->validated(),
                ['session_id' => $id]
            ));
            return ResponseFormatter::success($session, 'Session closed.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }
}
