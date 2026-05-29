<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\Repositories\PricingTierRepositoryInterface;
use Modules\Pricing\Presentation\Http\Controllers\Concerns\HandlesPricingRepositoryResponses;
use Modules\Pricing\Presentation\Http\Requests\ListPricingRecordRequest;
use Modules\Pricing\Presentation\Http\Requests\UpsertPricingTierRequest;
use Modules\Pricing\Presentation\Http\Resources\PricingRecordResource;

final class PricingTierController extends Controller
{
    use HandlesPricingRepositoryResponses;

    public function __construct(private readonly PricingTierRepositoryInterface $repository) {}

    public function index(ListPricingRecordRequest $request): JsonResponse
    {
        return $this->listRecords($this->repository, $request->validated());
    }

    public function show(int|string $pricingTier): JsonResponse|PricingRecordResource
    {
        return $this->showRecord($this->repository, $pricingTier);
    }

    public function store(UpsertPricingTierRequest $request): JsonResponse
    {
        return $this->createRecord($this->repository, $request->validated());
    }

    public function update(UpsertPricingTierRequest $request, int|string $pricingTier): JsonResponse|PricingRecordResource
    {
        return $this->updateRecord($this->repository, $pricingTier, $request->validated());
    }

    public function destroy(int|string $pricingTier): JsonResponse
    {
        return $this->deleteRecord($this->repository, $pricingTier);
    }
}
