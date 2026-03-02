<?php

declare(strict_types=1);

namespace Modules\Customization\Domain\Contracts;

use Modules\Customization\Domain\Entities\CustomField;

interface CustomFieldRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?CustomField;
    public function findAll(int $tenantId, string $entityType, int $page, int $perPage): array;
    public function findByEntityType(int $tenantId, string $entityType): array;
    public function save(CustomField $field): CustomField;
    public function delete(int $id, int $tenantId): void;
}
