<?php

namespace Modules\Document\Application\Actions;

use Modules\Document\Domain\Exceptions\DocumentValidationException;
use Modules\Document\Domain\Repositories\DocumentItemDefinitionRepositoryInterface;

class DocumentFieldValidationService
{
    public function __construct(
        private readonly DocumentItemDefinitionRepositoryInterface $itemDefinitionRepository
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $schema
     */
    public function validateHeaderData(array $data, array $schema): void
    {
        foreach ($schema as $field => $rules) {
            if (! is_array($rules)) {
                continue;
            }

            $isRequired = (bool) ($rules['required'] ?? false);
            if (! $isRequired) {
                continue;
            }

            $value = $data[$field] ?? null;
            if ($value === null || $value === '') {
                throw new DocumentValidationException("Header field [{$field}] is required.");
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateItemData(array $data, string $itemType, int $tenantId): void
    {
        $definition = $this->itemDefinitionRepository->findActiveByItemType($tenantId, $itemType);
        if ($definition === null) {
            return;
        }

        $schema = $definition['field_schema'] ?? [];
        if (! is_array($schema)) {
            return;
        }

        foreach ($schema as $field => $rules) {
            if (! is_array($rules)) {
                continue;
            }

            $isRequired = (bool) ($rules['required'] ?? false);
            if (! $isRequired) {
                continue;
            }

            $value = $data[$field] ?? null;
            if ($value === null || $value === '') {
                throw new DocumentValidationException(
                    "Item field [{$field}] is required for item type [{$itemType}]."
                );
            }
        }
    }
}
