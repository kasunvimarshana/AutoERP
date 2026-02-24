<?php

namespace Modules\SubscriptionBilling\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\SubscriptionBilling\Application\UseCases\CreateSubscriptionPlanUseCase;
use Modules\SubscriptionBilling\Infrastructure\Repositories\SubscriptionPlanRepository;
use Modules\SubscriptionBilling\Presentation\Requests\StoreSubscriptionPlanRequest;
use Modules\Shared\Application\ResponseFormatter;

class SubscriptionPlanController extends Controller
{
    public function __construct(
        private CreateSubscriptionPlanUseCase $createUseCase,
        private SubscriptionPlanRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->findAll([], 15));
    }

    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        $plan = $this->createUseCase->execute(array_merge(
            $request->validated(),
            ['tenant_id' => auth()->user()?->tenant_id],
        ));
        return ResponseFormatter::success($plan, 'Subscription plan created.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $plan = $this->repo->findById($id);
        if (! $plan) {
            return ResponseFormatter::error('Subscription plan not found.', [], 404);
        }
        return ResponseFormatter::success($plan);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repo->delete($id);
        return ResponseFormatter::success(null, 'Subscription plan deleted.');
    }
}
