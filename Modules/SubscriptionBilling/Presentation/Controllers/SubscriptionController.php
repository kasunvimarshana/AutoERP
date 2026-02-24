<?php

namespace Modules\SubscriptionBilling\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\SubscriptionBilling\Application\UseCases\CancelSubscriptionUseCase;
use Modules\SubscriptionBilling\Application\UseCases\CreateSubscriptionUseCase;
use Modules\SubscriptionBilling\Application\UseCases\RenewSubscriptionUseCase;
use Modules\SubscriptionBilling\Infrastructure\Repositories\SubscriptionRepository;
use Modules\SubscriptionBilling\Presentation\Requests\StoreSubscriptionRequest;
use Modules\Shared\Application\ResponseFormatter;

class SubscriptionController extends Controller
{
    public function __construct(
        private CreateSubscriptionUseCase $createUseCase,
        private RenewSubscriptionUseCase  $renewUseCase,
        private CancelSubscriptionUseCase $cancelUseCase,
        private SubscriptionRepository    $repo,
    ) {}

    public function index(): JsonResponse
    {
        return ResponseFormatter::paginated($this->repo->findAll([], 15));
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        try {
            $subscription = $this->createUseCase->execute(array_merge(
                $request->validated(),
                ['tenant_id' => auth()->user()?->tenant_id],
            ));
            return ResponseFormatter::success($subscription, 'Subscription created.', 201);
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        $subscription = $this->repo->findById($id);
        if (! $subscription) {
            return ResponseFormatter::error('Subscription not found.', [], 404);
        }
        return ResponseFormatter::success($subscription);
    }

    public function renew(string $id): JsonResponse
    {
        try {
            $subscription = $this->renewUseCase->execute($id);
            return ResponseFormatter::success($subscription, 'Subscription renewed.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }

    public function cancel(string $id): JsonResponse
    {
        try {
            $subscription = $this->cancelUseCase->execute($id);
            return ResponseFormatter::success($subscription, 'Subscription cancelled.');
        } catch (\DomainException $e) {
            return ResponseFormatter::error($e->getMessage(), [], 422);
        }
    }
}
