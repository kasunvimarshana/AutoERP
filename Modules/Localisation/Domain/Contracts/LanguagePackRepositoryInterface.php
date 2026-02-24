<?php

namespace Modules\Localisation\Domain\Contracts;

interface LanguagePackRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByTenant(string $tenantId): iterable;
    public function findByLocale(string $tenantId, string $locale): ?object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): void;
}
