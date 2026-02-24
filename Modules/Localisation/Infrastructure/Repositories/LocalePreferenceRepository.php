<?php

namespace Modules\Localisation\Infrastructure\Repositories;

use Modules\Localisation\Domain\Contracts\LocalePreferenceRepositoryInterface;
use Modules\Localisation\Infrastructure\Models\LocalePreferenceModel;

class LocalePreferenceRepository implements LocalePreferenceRepositoryInterface
{
    public function findByUser(string $userId): ?object
    {
        return LocalePreferenceModel::where('user_id', $userId)->first();
    }

    public function upsert(array $data): object
    {
        $model = LocalePreferenceModel::firstOrNew(['user_id' => $data['user_id']]);
        $model->fill($data);
        $model->save();

        return $model->fresh();
    }
}
