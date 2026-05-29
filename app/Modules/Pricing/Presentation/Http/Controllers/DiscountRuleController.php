<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\Repositories\DiscountRuleRepositoryInterface;
use Modules\Pricing\Presentation\Http\Controllers\Concerns\HandlesPricingRepositoryResponses;
use Modules\Pricing\Presentation\Http\Requests\ListPricingRecordRequest;
use Modules\Pricing\Presentation\Http\Requests\UpsertDiscountRuleRequest;
use Modules\Pricing\Presentation\Http\Resources\PricingRecordResource;

final class DiscountRuleController extends Controller
{
    use HandlesPricingRepositoryResponses;

    public function __construct(private readonly DiscountRuleRepositoryInterface $repository) {}

    public function index(ListPricingRecordRequest $request): JsonResponse
    {
        return $this->listRecords($this->repository, $request->validated());
    }

    public function show(int|string $discountRule): JsonResponse|PricingRecordResource
    {
        return $this->showRecord($this->repository, $discountRule);
    }

    public function store(UpsertDiscountRuleRequest $request): JsonResponse
    {
        return $this->createRecord($this->repository, $request->validated());
    }

    public function update(UpsertDiscountRuleRequest $request, int|string $discountRule): JsonResponse|PricingRecordResource
    {
        return $this->updateRecord($this->repository, $discountRule, $request->validated());
    }

    public function destroy(int|string $discountRule): JsonResponse
    {
        return $this->deleteRecord($this->repository, $discountRule);
    }
}
