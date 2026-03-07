<?php

namespace App\Repositories;

use App\Models\Webhook;

class WebhookRepository extends BaseRepository
{
    protected array $searchableColumns = ['url', 'description'];

    protected function model(): string
    {
        return Webhook::class;
    }

    public function getActiveForEvent(string $event, int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->withoutGlobalScope('tenant')
                           ->where('tenant_id', $tenantId)
                           ->where('status', 'active')
                           ->where(function ($q) use ($event) {
                               $q->whereJsonContains('events', $event)
                                 ->orWhereJsonContains('events', '*');
                           })
                           ->get();
    }

    public function getActiveForTenant(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->withoutGlobalScope('tenant')
                           ->where('tenant_id', $tenantId)
                           ->where('status', 'active')
                           ->get();
    }
}
