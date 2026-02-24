<?php

namespace Modules\Helpdesk\Infrastructure\Repositories;

use Modules\Helpdesk\Domain\Contracts\KbArticleRepositoryInterface;
use Modules\Helpdesk\Infrastructure\Models\KbArticleModel;

class KbArticleRepository implements KbArticleRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return KbArticleModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return KbArticleModel::where('tenant_id', $tenantId)->get();
    }

    public function findPublished(string $tenantId): iterable
    {
        return KbArticleModel::where('tenant_id', $tenantId)
            ->where('status', 'published')
            ->get();
    }

    public function create(array $data): object
    {
        return KbArticleModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = KbArticleModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        KbArticleModel::findOrFail($id)->delete();
    }
}
