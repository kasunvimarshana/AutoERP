<?php

declare(strict_types=1);

namespace Modules\Configuration\Support;

use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;
use Modules\Configuration\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\DTOs\ConfigurationValueData;
use Modules\Configuration\Entities\ConfigurationEntry;
use Modules\Configuration\Services\Contracts\ConfigurationDomainServiceInterface;
use Modules\Core\DTOs\DataRecord;

final class ConfigurationRecordMapper implements ConfigurationRecordMapperInterface
{
    public function __construct(private readonly ConfigurationDomainServiceInterface $domain) {}

    public function toValueData(DataRecord $record): ConfigurationValueData
    {
        $entry = $this->toEntry($record);

        return new ConfigurationValueData(
            $entry->key(),
            $entry->value(),
            $entry->source(),
            $entry->description(),
            $entry->updatedAt(),
            $entry->scope(),
            $entry->tenantId(),
            $entry->organizationUnitId(),
            $entry->scope(),
        );
    }

    public function extractId(DataRecord $record): int|string
    {
        return $record->id();
    }

    private function toEntry(DataRecord $record): ConfigurationEntry
    {
        $id = $record->id();

        $key = $this->domain->normalizeKey((string) $record->require('key'));
        $source = $this->domain->normalizeSource(
            ($record->get('source') !== null) ? (string) $record->get('source') : null,
        );
        $description = ($record->get('description') !== null) ? (string) $record->get('description') : null;
        $updatedAt = ($record->get('updated_at') !== null) ? (string) $record->get('updated_at') : null;
        $scope = ($record->get('scope') !== null)
            ? $this->domain->normalizeScope((string) $record->get('scope'))
            : ConfigurationScope::GLOBAL;
        $tenantId = ($record->get('tenant_id') !== null) ? (int) $record->get('tenant_id') : null;
        $organizationUnitId = ($record->get('organization_unit_id') !== null)
            ? (int) $record->get('organization_unit_id')
            : null;

        $valueType = ($record->get('value_type') !== null)
            ? (string) $record->get('value_type')
            : ConfigurationValueType::NULL;
        $storedValue = ($record->get('value') !== null) ? (string) $record->get('value') : '';

        $value = $this->domain->deserializeValue($storedValue, $valueType);

        return new ConfigurationEntry(
            $id,
            $key,
            $value,
            $source,
            $description,
            $updatedAt,
            $scope,
            $tenantId,
            $organizationUnitId,
        );
    }
}
