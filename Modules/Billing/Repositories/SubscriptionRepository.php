<?php

declare(strict_types=1);

namespace Modules\Billing\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Billing\Enums\SubscriptionStatus;
use Modules\Billing\Models\Subscription;
use Modules\Core\Repositories\BaseRepository;

class SubscriptionRepository extends BaseRepository
{
    protected function makeModel(): Model
    {
        return new Subscription;
    }

    public function findByCode(string $code): ?Subscription
    {
        return $this->model->where('subscription_code', $code)->first();
    }

    public function getActiveSubscriptions(): Collection
    {
        return $this->model
            ->whereIn('status', [
                SubscriptionStatus::Trial,
                SubscriptionStatus::Active,
                SubscriptionStatus::PastDue,
            ])
            ->get();
    }

    public function getByUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function getByOrganization(int $organizationId): Collection
    {
        return $this->model->where('organization_id', $organizationId)->get();
    }

    public function getExpiringSubscriptions(int $days = 30): Collection
    {
        return $this->model
            ->where('status', SubscriptionStatus::Active)
            ->where('current_period_end', '<=', now()->addDays($days))
            ->where('current_period_end', '>=', now())
            ->get();
    }

    public function searchSubscriptions(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['plan_id'])) {
            $query->where('plan_id', $filters['plan_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('subscription_code', 'like', "%{$search}%");
        }

        return $query
            ->with(['plan', 'user', 'organization'])
            ->latest()
            ->paginate($perPage);
    }
}
