<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Pricing\Application\Contracts\ResolvePriceServiceInterface;
use Modules\Pricing\Infrastructure\Http\Requests\ResolvePriceRequest;

class PriceResolutionController extends AuthorizedController
{
    public function __construct(private readonly ResolvePriceServiceInterface $resolvePriceService) {}

    public function resolve(ResolvePriceRequest $request): JsonResponse
    {
        $resolved = $this->resolvePriceService->execute($request->validated());

        return response()->json(['data' => $resolved]);
    }
}
