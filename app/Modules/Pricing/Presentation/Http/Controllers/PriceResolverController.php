<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\Contracts\Services\PriceResolverServiceInterface;

final class PriceResolverController extends Controller
{
    public function __construct(
        private readonly PriceResolverServiceInterface $priceResolverService,
    ) {}

    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'min:1'],
            'item_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $result = $this->priceResolverService->resolvePrice($validated);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json($result->valueOrFail());
    }
}
