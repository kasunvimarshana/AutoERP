<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Pricing\Application\Contracts\Services\PricingUsageSummaryServiceInterface;
use Modules\Pricing\Application\Contracts\Services\PricingRuleServiceInterface;
use Modules\Pricing\Application\Repositories\PricingRuleRepositoryInterface;
use Modules\Pricing\Presentation\Http\Controllers\Concerns\HandlesPricingRepositoryResponses;
use Modules\Pricing\Presentation\Http\Requests\ListPricingRecordRequest;
use Modules\Pricing\Presentation\Http\Requests\UpsertPricingRuleRequest;
use Modules\Pricing\Presentation\Http\Resources\PricingRecordResource;

final class PricingRuleController extends Controller
{
    use HandlesPricingRepositoryResponses;

    public function __construct(
        private readonly PricingRuleRepositoryInterface $repository,
        private readonly PricingRuleServiceInterface $service,
        private readonly PricingUsageSummaryServiceInterface $usageSummaryService,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function index(ListPricingRecordRequest $request): JsonResponse
    {
        return $this->listRecords($this->repository, $request->validated());
    }

    public function show(int|string $pricingRule): JsonResponse|PricingRecordResource
    {
        return $this->showRecord($this->repository, $pricingRule);
    }

    public function store(UpsertPricingRuleRequest $request): JsonResponse|PricingRecordResource
    {
        $result = $this->service->createPricingRule($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PricingRecordResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPricingRuleRequest $request, int|string $pricingRule): JsonResponse|PricingRecordResource
    {
        $result = $this->service->updatePricingRule($pricingRule, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PRICING_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PricingRecordResource($result->valueOrFail());
    }

    public function destroy(int|string $pricingRule): JsonResponse
    {
        return $this->deleteRecord($this->repository, $pricingRule);
    }

    public function activate(int|string $pricingRule): JsonResponse|PricingRecordResource
    {
        return $this->changeActiveState($pricingRule, true);
    }

    public function deactivate(int|string $pricingRule): JsonResponse|PricingRecordResource
    {
        return $this->changeActiveState($pricingRule, false);
    }

    public function usage(int|string $pricingRule): JsonResponse
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null) {
            return response()->json(['message' => 'Current tenant context is required.'], 422);
        }

        if ($this->repository->findById($pricingRule) === null) {
            return response()->json(['message' => 'Pricing rule not found.'], 404);
        }

        return response()->json([
            'data' => $this->usageSummaryService->summarizePricingRule((int) $pricingRule, (int) $tenantId),
        ]);
    }

    private function changeActiveState(int|string $pricingRule, bool $isActive): JsonResponse|PricingRecordResource
    {
        $result = $this->service->updatePricingRule($pricingRule, ['is_active' => $isActive]);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'PRICING_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PricingRecordResource($result->valueOrFail());
    }
}
