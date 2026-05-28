<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Support;

final class AuditEventPayloadNormalizer
{
    /**
     * @return array<string, mixed>
     */
    public static function normalize(string $eventName, mixed $eventPayload): array
    {
        $payload = self::toArray($eventPayload);

        $auditableType = self::stringValue($payload, ['auditable_type', 'entity_type', 'model', 'type']) ?? $eventName;
        $auditableId = self::stringValue($payload, ['auditable_id', 'entity_id', 'id', 'uuid']) ?? 'n/a';

        return [
            'event' => trim($eventName),
            'auditable_type' => trim($auditableType),
            'auditable_id' => trim($auditableId),
            'tenant_id' => self::intValue($payload, ['tenant_id']),
            'organization_unit_id' => self::intValue($payload, ['organization_unit_id']),
            'user_id' => self::intValue($payload, ['user_id']),
            'old_values' => self::arrayValue($payload, ['old_values', 'before']),
            'new_values' => self::arrayValue($payload, ['new_values', 'after']),
            'metadata' => $payload,
            'url' => self::stringValue($payload, ['url']),
            'ip_address' => self::stringValue($payload, ['ip_address']),
            'user_agent' => self::stringValue($payload, ['user_agent']),
            'occurred_at' => self::stringValue($payload, ['occurred_at']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function toArray(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_object($payload)) {
            if (method_exists($payload, 'toArray')) {
                $array = $payload->toArray();

                return is_array($array) ? $array : ['value' => $array];
            }

            if ($payload instanceof \JsonSerializable) {
                $serialized = $payload->jsonSerialize();

                return is_array($serialized) ? $serialized : ['value' => $serialized];
            }

            return get_object_vars($payload);
        }

        if ($payload === null) {
            return [];
        }

        return ['value' => $payload];
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $keys
     */
    private static function stringValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;
            if (is_scalar($value)) {
                $resolved = trim((string) $value);

                if ($resolved !== '') {
                    return $resolved;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $keys
     */
    private static function intValue(array $payload, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $keys
     * @return array<string, mixed>|null
     */
    private static function arrayValue(array $payload, array $keys): ?array
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;
            if (is_array($value)) {
                return $value;
            }
        }

        return null;
    }
}
