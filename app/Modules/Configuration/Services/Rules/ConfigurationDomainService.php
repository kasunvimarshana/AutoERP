<?php

declare(strict_types=1);

namespace Modules\Configuration\Services\Rules;

use Modules\Configuration\Constants\ConfigurationErrorCode;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationSource;
use Modules\Configuration\Constants\ConfigurationValueType;
use Modules\Configuration\Services\Contracts\ConfigurationDomainServiceInterface;
use Modules\Core\Exceptions\DomainException;

final class ConfigurationDomainService implements ConfigurationDomainServiceInterface
{
    private const KEY_PATTERN = '/^[a-z0-9._-]+$/i';

    private const SOURCE_MAX_LENGTH = 20;

    private const VALUE_TYPE_MAX_LENGTH = 20;

    public function normalizeKey(string $key): string
    {
        $normalized = trim($key);

        if ($normalized === '' || preg_match(self::KEY_PATTERN, $normalized) !== 1) {
            throw new DomainException(ConfigurationErrorCode::INVALID_KEY.': Invalid configuration key format.');
        }

        return $normalized;
    }

    public function normalizeSource(?string $source): string
    {
        $resolved = $source === null ? '' : trim($source);

        if ($resolved === '') {
            return ConfigurationSource::DATABASE;
        }

        if (mb_strlen($resolved) > self::SOURCE_MAX_LENGTH) {
            throw new DomainException(ConfigurationErrorCode::INVALID_SOURCE.': Source exceeds schema limit.');
        }

        if (! in_array($resolved, [ConfigurationSource::DATABASE, ConfigurationSource::ENVIRONMENT, ConfigurationSource::RUNTIME], true)) {
            throw new DomainException(ConfigurationErrorCode::INVALID_SOURCE.': Unsupported source value.');
        }

        return $resolved;
    }

    public function normalizeScope(?string $scope): string
    {
        $resolved = $scope === null ? '' : trim($scope);

        if ($resolved === '') {
            return ConfigurationScope::GLOBAL;
        }

        if (! in_array($resolved, [ConfigurationScope::GLOBAL, ConfigurationScope::TENANT, ConfigurationScope::ORGANIZATION_UNIT], true)) {
            throw new DomainException(ConfigurationErrorCode::INVALID_SCOPE.': Unsupported scope value.');
        }

        return $resolved;
    }

    public function normalizeDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $value = trim($description);

        return $value === '' ? null : $value;
    }

    public function parseCliValue(string $raw): mixed
    {
        $trimmed = trim($raw);
        $lowered = strtolower($trimmed);

        if ($lowered === 'null') {
            return null;
        }

        if ($lowered === 'true') {
            return true;
        }

        if ($lowered === 'false') {
            return false;
        }

        if (preg_match('/^-?\d+$/', $trimmed) === 1) {
            return (int) $trimmed;
        }

        if (preg_match('/^-?\d+\.\d+$/', $trimmed) === 1) {
            return (float) $trimmed;
        }

        if (
            str_starts_with($trimmed, '{')
            || str_starts_with($trimmed, '[')
        ) {
            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $raw;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function serializeValue(mixed $value): array
    {
        if ($value === null) {
            return ['', ConfigurationValueType::NULL];
        }

        if (is_bool($value)) {
            return [$value ? '1' : '0', ConfigurationValueType::BOOLEAN];
        }

        if (is_int($value)) {
            return [(string) $value, ConfigurationValueType::INTEGER];
        }

        if (is_float($value)) {
            return [(string) $value, ConfigurationValueType::FLOAT];
        }

        if (is_array($value)) {
            return [json_encode($value, JSON_THROW_ON_ERROR), ConfigurationValueType::JSON];
        }

        if (is_string($value)) {
            return [$value, ConfigurationValueType::STRING];
        }

        throw new DomainException(ConfigurationErrorCode::INVALID_VALUE.': Unsupported value type.');
    }

    public function assertValueType(string $valueType): void
    {
        if (mb_strlen($valueType) > self::VALUE_TYPE_MAX_LENGTH) {
            throw new DomainException(ConfigurationErrorCode::INVALID_VALUE.': Value type exceeds schema limit.');
        }

        if (! in_array(
            $valueType,
            [
                ConfigurationValueType::NULL,
                ConfigurationValueType::STRING,
                ConfigurationValueType::INTEGER,
                ConfigurationValueType::FLOAT,
                ConfigurationValueType::BOOLEAN,
                ConfigurationValueType::JSON,
                ConfigurationValueType::ENCRYPTED,
            ],
            true,
        )) {
            throw new DomainException(ConfigurationErrorCode::INVALID_VALUE.': Unsupported value type.');
        }
    }

    public function deserializeValue(string $storedValue, string $valueType): mixed
    {
        $this->assertValueType($valueType);

        return match ($valueType) {
            ConfigurationValueType::NULL => null,
            ConfigurationValueType::BOOLEAN => $storedValue === '1',
            ConfigurationValueType::INTEGER => (int) $storedValue,
            ConfigurationValueType::FLOAT => (float) $storedValue,
            ConfigurationValueType::JSON => json_decode($storedValue, true, 512, JSON_THROW_ON_ERROR),
            ConfigurationValueType::ENCRYPTED => $storedValue,
            default => $storedValue,
        };
    }
}
