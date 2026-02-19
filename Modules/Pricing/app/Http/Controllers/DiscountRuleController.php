<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Pricing\Requests\StoreDiscountRuleRequest;
use Modules\Pricing\Resources\DiscountRuleResource;
use Modules\Pricing\Services\DiscountRuleService;

class DiscountRuleController extends Controller
{
    public function __construct(
        private readonly DiscountRuleService $discountRuleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $rules = $this->discountRuleService->getAll($filters);

        return $this->successResponse(
            DiscountRuleResource::collection($rules),
            'Discount rules retrieved successfully'
        );
    }

    public function store(StoreDiscountRuleRequest $request): JsonResponse
    {
        $rule = $this->discountRuleService->create($request->validated());

        return $this->createdResponse(
            new DiscountRuleResource($rule),
            'Discount rule created successfully'
        );
    }

    public function show(int $id): JsonResponse
    {
        $rule = $this->discountRuleService->getById($id);

        return $this->successResponse(
            new DiscountRuleResource($rule),
            'Discount rule retrieved successfully'
        );
    }

    public function update(StoreDiscountRuleRequest $request, int $id): JsonResponse
    {
        $rule = $this->discountRuleService->update($id, $request->validated());

        return $this->successResponse(
            new DiscountRuleResource($rule),
            'Discount rule updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->discountRuleService->delete($id);

        return $this->successResponse(null, 'Discount rule deleted successfully');
    }
}
