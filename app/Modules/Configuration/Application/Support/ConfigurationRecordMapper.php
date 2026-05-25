<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Support;

use Modules\Configuration\Application\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\Application\DTOs\ConfigurationValueData;
use Modules\Configuration\Domain\Constants\ConfigurationValueType;
use Modules\Configuration\Domain\Contracts\ConfigurationDomainServiceInterface;
use Modules\Configuration\Domain\Entities\ConfigurationEntry;
use Modules\Core\Application\DTO\DataRecord;

final class ConfigurationRecordMapper implements ConfigurationRecordMapperInterface
{
    public function __construct(private readonly ConfigurationDomainServiceInterface $domain)
    {
    }

    public function toValueData(DataRecord $record): ConfigurationValueData
    {
        $entry = $this->toEntry($record);

        return new ConfigurationValueData(
            $entry->key(),
            $entry->value(),
            $entry->source(),
            $entry->description(),
            $entry->updatedAt(),
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

        $valueType = ($record->get('value_type') !== null)
            ? (string) $record->get('value_type')
            : ConfigurationValueType::NULL;
        $storedValue = ($record->get('value') !== null) ? (string) $record->get('value') : '';

        $value = $this->domain->deserializeValue($storedValue, $valueType);

        return new ConfigurationEntry($id, $key, $value, $source, $description, $updatedAt);
    }
}
