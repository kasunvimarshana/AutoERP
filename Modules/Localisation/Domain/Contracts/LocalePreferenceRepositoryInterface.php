<?php

namespace Modules\Localisation\Domain\Contracts;

interface LocalePreferenceRepositoryInterface
{
    public function findByUser(string $userId): ?object;
    public function upsert(array $data): object;
}
