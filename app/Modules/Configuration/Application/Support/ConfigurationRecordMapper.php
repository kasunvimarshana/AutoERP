<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Support;

use Modules\Configuration\Application\Contracts\ConfigurationRecordMapperInterface;
use Modules\Configuration\Application\DTOs\ConfigurationValueData;
use Modules\Configuration\Domain\Constants\ConfigurationErrorCode;
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
        $id = $record->values['id'] ?? null;

        if (! is_int($id) && ! is_string($id)) {
            throw new \RuntimeException(ConfigurationErrorCode::INVALID_RECORD . ': Missing identifier.');
        }

        return $id;
    }

    private function toEntry(DataRecord $record): ConfigurationEntry
    {
        $values = $record->values;

        $id = $values['id'] ?? null;
        if (! is_int($id) && ! is_string($id)) {
            throw new \RuntimeException(ConfigurationErrorCode::INVALID_RECORD . ': Missing identifier.');
        }

        $key = $this->domain->normalizeKey((string) ($values['key'] ?? ''));
        $source = $this->domain->normalizeSource(isset($values['source']) ? (string) $values['source'] : null);
        $description = isset($values['description']) ? (string) $values['description'] : null;
        $updatedAt = isset($values['updated_at']) ? (string) $values['updated_at'] : null;

        $valueType = isset($values['value_type'])
            ? (string) $values['value_type']
            : ConfigurationValueType::NULL;
        $storedValue = isset($values['value']) ? (string) $values['value'] : '';

        $value = $this->domain->deserializeValue($storedValue, $valueType);

        return new ConfigurationEntry($id, $key, $value, $source, $description, $updatedAt);
    }
}
