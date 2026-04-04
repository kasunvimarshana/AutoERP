<?php

namespace Modules\Returns\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Returns\Application\Contracts\FailQualityCheckServiceInterface;
use Modules\Returns\Application\Contracts\PassQualityCheckServiceInterface;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;
use Modules\Returns\Infrastructure\Http\Resources\StockReturnLineResource;

class ReturnLineController extends Controller
{
    public function __construct(
        private readonly StockReturnLineRepositoryInterface $lineRepository,
        private readonly PassQualityCheckServiceInterface $passService,
        private readonly FailQualityCheckServiceInterface $failService,
    ) {}

    public function passQualityCheck(Request $request, int $id): JsonResponse
    {
        $request->validate(['checked_by' => 'required|integer']);

        $line = $this->lineRepository->findById($id);
        if (!$line) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->passService->execute($line, (int) $request->input('checked_by'));

            return response()->json(new StockReturnLineResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function failQualityCheck(Request $request, int $id): JsonResponse
    {
        $request->validate(['checked_by' => 'required|integer']);

        $line = $this->lineRepository->findById($id);
        if (!$line) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->failService->execute($line, (int) $request->input('checked_by'));

            return response()->json(new StockReturnLineResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
