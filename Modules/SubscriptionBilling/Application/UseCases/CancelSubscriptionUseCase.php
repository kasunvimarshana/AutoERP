<?php

namespace Modules\SubscriptionBilling\Application\UseCases;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\SubscriptionBilling\Domain\Events\SubscriptionCancelled;

class CancelSubscriptionUseCase
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepo,
    ) {}

    public function execute(string $subscriptionId): object
    {
        return DB::transaction(function () use ($subscriptionId) {
            $subscription = $this->subscriptionRepo->findById($subscriptionId);

            if (! $subscription) {
                throw new ModelNotFoundException("Subscription [{$subscriptionId}] not found.");
            }

            if ($subscription->status === 'cancelled') {
                throw new DomainException("Subscription [{$subscriptionId}] is already cancelled.");
            }

            $updated = $this->subscriptionRepo->update($subscriptionId, [
                'status'       => 'cancelled',
                'cancelled_at' => now()->toDateTimeString(),
            ]);

            Event::dispatch(new SubscriptionCancelled($subscriptionId));

            return $updated;
        });
    }
}
