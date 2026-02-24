<?php

namespace Modules\Localisation\Infrastructure\Repositories;

use Modules\Localisation\Domain\Contracts\LanguagePackRepositoryInterface;
use Modules\Localisation\Infrastructure\Models\LanguagePackModel;

class LanguagePackRepository implements LanguagePackRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return LanguagePackModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return LanguagePackModel::where('tenant_id', $tenantId)->get();
    }

    public function findByLocale(string $tenantId, string $locale): ?object
    {
        return LanguagePackModel::where('tenant_id', $tenantId)
            ->where('locale', $locale)
            ->first();
    }

    public function create(array $data): object
    {
        return LanguagePackModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = LanguagePackModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        LanguagePackModel::findOrFail($id)->delete();
    }
}
