<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\Services\PricingService;
use Modules\Pricing\Domain\Exceptions\PricingIntegrityException;
use Modules\Pricing\Domain\Exceptions\PricingRecordNotFoundException;
use Modules\Pricing\Presentation\Http\Requests\ResolvePriceRequest;

class PricingResolutionController extends Controller
{
    public function __construct(private readonly PricingService $pricing) {}

    public function resolve(ResolvePriceRequest $request, int|string $tenant): JsonResponse
    {
        try {
            return response()->json($this->pricing->resolve($tenant, $request->validated()));
        } catch (PricingIntegrityException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (PricingRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
