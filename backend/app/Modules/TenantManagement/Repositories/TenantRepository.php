<?php

namespace App\Modules\TenantManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\TenantManagement\Models\Tenant;

class TenantRepository extends BaseRepository
{
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function findByDomain(string $domain)
    {
        return $this->model->where('domain', $domain)->first();
    }

    public function findByUuid(string $uuid)
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function getActive()
    {
        return $this->model->active()->get();
    }

    public function getWithActiveSubscription()
    {
        return $this->model->withActiveSubscription()->get();
    }

    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['subscription_status'])) {
            $query->where('subscription_status', $criteria['subscription_status']);
        }

        return $query->paginate($criteria['per_page'] ?? 15);
    }

    public function updateSubscription(int $id, array $data)
    {
        return $this->update($id, [
            'subscription_status' => $data['subscription_status'],
            'subscription_plan' => $data['subscription_plan'] ?? null,
            'subscription_started_at' => $data['subscription_started_at'] ?? null,
            'subscription_expires_at' => $data['subscription_expires_at'] ?? null,
        ]);
    }
}
