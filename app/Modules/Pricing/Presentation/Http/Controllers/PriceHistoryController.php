<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\Repositories\PriceHistoryRepositoryInterface;
use Modules\Pricing\Presentation\Http\Controllers\Concerns\HandlesPricingRepositoryResponses;
use Modules\Pricing\Presentation\Http\Requests\ListPricingRecordRequest;
use Modules\Pricing\Presentation\Http\Resources\PricingRecordResource;

final class PriceHistoryController extends Controller
{
    use HandlesPricingRepositoryResponses;

    public function __construct(private readonly PriceHistoryRepositoryInterface $repository) {}

    public function index(ListPricingRecordRequest $request): JsonResponse
    {
        return $this->listRecords($this->repository, $request->validated());
    }

    public function show(int|string $priceHistory): JsonResponse|PricingRecordResource
    {
        return $this->showRecord($this->repository, $priceHistory);
    }
}
