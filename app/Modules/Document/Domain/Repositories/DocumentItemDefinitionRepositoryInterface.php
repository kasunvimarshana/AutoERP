<?php

namespace Modules\Document\Domain\Repositories;

interface DocumentItemDefinitionRepositoryInterface
{
    /**
     * @return array{
     *   item_type_code: string,
     *   field_schema: array<string, mixed>,
     *   validation_rules: array<string, mixed>,
     *   calculation_rule: string|null
     * }|null
     */
    public function findActiveByItemType(int $tenantId, string $itemType): ?array;
}
