<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\StoreSubscriptionRequest;
use Modules\Billing\Http\Requests\UpdateSubscriptionRequest;
use Modules\Billing\Http\Resources\SubscriptionResource;
use Modules\Billing\Models\Subscription;
use Modules\Billing\Repositories\SubscriptionRepository;
use Modules\Billing\Services\SubscriptionService;
use Modules\Core\Http\Responses\ApiResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Subscription::class);

        $filters = [
            'status' => $request->status,
            'plan_id' => $request->plan_id,
            'user_id' => $request->user_id,
            'organization_id' => $request->organization_id,
            'search' => $request->search,
        ];

        $perPage = $request->get('per_page', 15);
        $subscriptions = $this->subscriptionRepository->searchSubscriptions(
            array_filter($filters, fn ($value) => ! is_null($value)),
            $perPage
        );

        return ApiResponse::paginated(
            $subscriptions->setCollection(
                $subscriptions->getCollection()->map(fn ($sub) => new SubscriptionResource($sub))
            ),
            'Subscriptions retrieved successfully'
        );
    }

    /**
     * Store a newly created subscription.
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $this->authorize('create', Subscription::class);

        $subscription = $this->subscriptionService->createSubscription($request->validated());

        return ApiResponse::created(
            new SubscriptionResource($subscription->load(['plan', 'user', 'organization'])),
            'Subscription created successfully'
        );
    }

    /**
     * Display the specified subscription.
     */
    public function show(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findOrFail($id);
        $this->authorize('view', $subscription);

        $subscription->load(['plan', 'user', 'organization', 'payments', 'usages']);

        return ApiResponse::success(
            new SubscriptionResource($subscription),
            'Subscription retrieved successfully'
        );
    }

    /**
     * Update the specified subscription.
     */
    public function update(UpdateSubscriptionRequest $request, int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findOrFail($id);
        $this->authorize('update', $subscription);

        $subscription = $this->subscriptionRepository->update($id, $request->validated());

        return ApiResponse::success(
            new SubscriptionResource($subscription->fresh()->load(['plan', 'user', 'organization'])),
            'Subscription updated successfully'
        );
    }

    /**
     * Remove the specified subscription.
     */
    public function destroy(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findOrFail($id);
        $this->authorize('delete', $subscription);

        $this->subscriptionRepository->delete($id);

        return ApiResponse::success(
            null,
            'Subscription deleted successfully'
        );
    }

    /**
     * Renew a subscription.
     */
    public function renew(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findOrFail($id);
        $this->authorize('update', $subscription);

        $subscription = $this->subscriptionService->renewSubscription($id);

        return ApiResponse::success(
            new SubscriptionResource($subscription->load(['plan', 'user', 'organization'])),
            'Subscription renewed successfully'
        );
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findOrFail($id);
        $this->authorize('update', $subscription);

        $immediately = $request->boolean('immediately', false);
        $subscription = $this->subscriptionService->cancelSubscription($id, $immediately);

        return ApiResponse::success(
            new SubscriptionResource($subscription->load(['plan', 'user', 'organization'])),
            'Subscription cancelled successfully'
        );
    }

    /**
     * Suspend a subscription.
     */
    public function suspend(Request $request, int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findOrFail($id);
        $this->authorize('update', $subscription);

        $reason = $request->input('reason');
        $subscription = $this->subscriptionService->suspendSubscription($id, $reason);

        return ApiResponse::success(
            new SubscriptionResource($subscription->load(['plan', 'user', 'organization'])),
            'Subscription suspended successfully'
        );
    }

    /**
     * Reactivate a subscription.
     */
    public function reactivate(int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findOrFail($id);
        $this->authorize('update', $subscription);

        $subscription = $this->subscriptionService->reactivateSubscription($id);

        return ApiResponse::success(
            new SubscriptionResource($subscription->load(['plan', 'user', 'organization'])),
            'Subscription reactivated successfully'
        );
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(Request $request, int $id): JsonResponse
    {
        $subscription = $this->subscriptionRepository->findOrFail($id);
        $this->authorize('update', $subscription);

        $request->validate(['plan_id' => 'required|integer|exists:billing_plans,id']);

        $subscription = $this->subscriptionService->changePlan($id, $request->plan_id);

        return ApiResponse::success(
            new SubscriptionResource($subscription->load(['plan', 'user', 'organization'])),
            'Subscription plan changed successfully'
        );
    }
}
