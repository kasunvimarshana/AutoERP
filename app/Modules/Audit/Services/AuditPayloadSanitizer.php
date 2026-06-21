<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use InvalidArgumentException;

final class AuditPayloadSanitizer
{
    /** @param array<string, mixed>|null $payload @return array<string, mixed>|null */
    public function sanitize(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        /** @var array<string, mixed> $sanitized */
        $sanitized = $this->sanitizeValue($payload, 0);

        return $sanitized;
    }

    /** @param list<string> $tags @return list<string> */
    public function sanitizeTags(array $tags): array
    {
        $limit = max(1, (int) config('audit.payload.max_tags', 20));
        $resolved = [];

        $maxTagLength = max(1, (int) config('audit.payload.max_tag_length', 100));

        foreach ($tags as $tag) {
            if (! is_string($tag)) {
                throw new InvalidArgumentException('Audit event tags must be strings.');
            }

            $tag = trim($tag);
            if ($tag === '') {
                continue;
            }

            if (mb_strlen($tag) > $maxTagLength) {
                throw new InvalidArgumentException("Audit event tags may not exceed {$maxTagLength} characters.");
            }
            $resolved[] = $tag;
        }

        $resolved = array_values(array_unique($resolved));
        if (count($resolved) > $limit) {
            throw new InvalidArgumentException("Audit event may contain at most {$limit} tags.");
        }

        return $resolved;
    }

    public function assertPayloadSize(?array $changes, ?array $metadata, array $tags): void
    {
        $encoded = json_encode([
            'changes' => $changes,
            'metadata' => $metadata,
            'tags' => $tags,
        ], JSON_THROW_ON_ERROR);
        $maxBytes = max(1, (int) config('audit.payload.max_json_bytes', 65_536));

        if (strlen($encoded) > $maxBytes) {
            throw new InvalidArgumentException("Audit payload exceeds the {$maxBytes}-byte limit.");
        }
    }

    private function sanitizeValue(mixed $value, int $depth): mixed
    {
        $maxDepth = max(1, (int) config('audit.payload.max_depth', 8));
        if ($depth > $maxDepth) {
            throw new InvalidArgumentException("Audit payload exceeds the maximum nesting depth of {$maxDepth}.");
        }

        if (is_array($value)) {
            $maxItems = max(1, (int) config('audit.payload.max_items_per_level', 200));
            if (count($value) > $maxItems) {
                throw new InvalidArgumentException("Audit payload arrays may contain at most {$maxItems} items per level.");
            }

            $result = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && $this->isSensitiveKey($key)) {
                    $result[$key] = (string) config('audit.payload.redacted_value', '[REDACTED]');
                    continue;
                }

                $result[$key] = $this->sanitizeValue($item, $depth + 1);
            }

            return $result;
        }

        if (is_string($value)) {
            $this->assertStringLength($value);

            return $value;
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        throw new InvalidArgumentException('Audit payloads may contain only arrays, scalars, enums, dates, and null values.');
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = $this->normalizeKey($key);
        $sensitive = config('audit.payload.sensitive_keys', []);

        if (! is_array($sensitive)) {
            return false;
        }

        foreach ($sensitive as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $candidate = $this->normalizeKey($candidate);
            if ($normalized === $candidate || str_ends_with($normalized, '_'.$candidate)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeKey(string $key): string
    {
        $key = preg_replace('/(?<!^)[A-Z]/', '_$0', trim($key)) ?? $key;
        $key = strtolower(str_replace(['-', ' ', '.'], '_', $key));

        return trim(preg_replace('/_+/', '_', $key) ?? $key, '_');
    }

    private function assertStringLength(string $value): void
    {
        $maxLength = max(1, (int) config('audit.payload.max_string_length', 4_000));
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException("Audit payload string exceeds the {$maxLength}-character limit.");
        }
    }
}
