<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\Repositories\PricingRuleConditionRepositoryInterface;
use Modules\Pricing\Presentation\Http\Controllers\Concerns\HandlesPricingRepositoryResponses;
use Modules\Pricing\Presentation\Http\Requests\ListPricingRecordRequest;
use Modules\Pricing\Presentation\Http\Requests\UpsertPricingRuleConditionRequest;
use Modules\Pricing\Presentation\Http\Resources\PricingRecordResource;

final class PricingRuleConditionController extends Controller
{
    use HandlesPricingRepositoryResponses;

    public function __construct(private readonly PricingRuleConditionRepositoryInterface $repository) {}

    public function index(ListPricingRecordRequest $request): JsonResponse
    {
        return $this->listRecords($this->repository, $request->validated());
    }

    public function show(int|string $pricingRuleCondition): JsonResponse|PricingRecordResource
    {
        return $this->showRecord($this->repository, $pricingRuleCondition);
    }

    public function store(UpsertPricingRuleConditionRequest $request): JsonResponse
    {
        return $this->createRecord($this->repository, $request->validated());
    }

    public function update(
        UpsertPricingRuleConditionRequest $request,
        int|string $pricingRuleCondition,
    ): JsonResponse|PricingRecordResource {
        return $this->updateRecord($this->repository, $pricingRuleCondition, $request->validated());
    }

    public function destroy(int|string $pricingRuleCondition): JsonResponse
    {
        return $this->deleteRecord($this->repository, $pricingRuleCondition);
    }
}
