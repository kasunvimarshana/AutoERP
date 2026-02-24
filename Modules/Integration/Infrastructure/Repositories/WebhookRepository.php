<?php

namespace Modules\Integration\Infrastructure\Repositories;

use Modules\Integration\Domain\Contracts\WebhookRepositoryInterface;
use Modules\Integration\Infrastructure\Models\WebhookModel;

class WebhookRepository implements WebhookRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return WebhookModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return WebhookModel::where('tenant_id', $tenantId)->get();
    }

    public function findActiveByTenant(string $tenantId): iterable
    {
        return WebhookModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
    }

    public function create(array $data): object
    {
        return WebhookModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = WebhookModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        WebhookModel::findOrFail($id)->delete();
    }
}
