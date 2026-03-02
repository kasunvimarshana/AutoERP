<?php

declare(strict_types=1);

namespace Modules\Customization\Domain\Contracts;

use Modules\Customization\Domain\Entities\CustomFieldValue;

interface CustomFieldValueRepositoryInterface
{
    public function findByEntity(int $tenantId, string $entityType, int $entityId): array;
    public function replaceForEntity(int $tenantId, string $entityType, int $entityId, array $values): array;
    public function deleteByField(int $fieldId, int $tenantId): void;
}
