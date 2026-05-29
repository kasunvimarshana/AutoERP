<?php

declare(strict_types=1);

namespace Modules\Pricing\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pricing\Application\Repositories\DiscountRepositoryInterface;
use Modules\Pricing\Presentation\Http\Controllers\Concerns\HandlesPricingRepositoryResponses;
use Modules\Pricing\Presentation\Http\Requests\ListPricingRecordRequest;
use Modules\Pricing\Presentation\Http\Requests\UpsertDiscountRequest;
use Modules\Pricing\Presentation\Http\Resources\PricingRecordResource;

final class DiscountController extends Controller
{
    use HandlesPricingRepositoryResponses;

    public function __construct(private readonly DiscountRepositoryInterface $repository) {}

    public function index(ListPricingRecordRequest $request): JsonResponse
    {
        return $this->listRecords($this->repository, $request->validated());
    }

    public function show(int|string $discount): JsonResponse|PricingRecordResource
    {
        return $this->showRecord($this->repository, $discount);
    }

    public function store(UpsertDiscountRequest $request): JsonResponse
    {
        return $this->createRecord($this->repository, $request->validated());
    }

    public function update(UpsertDiscountRequest $request, int|string $discount): JsonResponse|PricingRecordResource
    {
        return $this->updateRecord($this->repository, $discount, $request->validated());
    }

    public function destroy(int|string $discount): JsonResponse
    {
        return $this->deleteRecord($this->repository, $discount);
    }
}
