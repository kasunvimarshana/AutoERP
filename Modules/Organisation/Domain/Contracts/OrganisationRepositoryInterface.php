<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Contracts;

use Modules\Organisation\Domain\Entities\Organisation;

interface OrganisationRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Organisation;

    public function findByCode(string $code, int $tenantId): ?Organisation;

    public function findChildren(int $parentId, int $tenantId): array;

    public function findRoots(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function save(Organisation $organisation): Organisation;

    public function delete(int $id, int $tenantId): void;
}
