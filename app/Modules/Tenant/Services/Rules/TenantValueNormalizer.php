<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Rules;

use InvalidArgumentException;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface;

final class TenantValueNormalizer implements TenantValueNormalizerInterface
{
    /** @param list<string> $centralHosts */
    public function __construct(private readonly array $centralHosts = []) {}

    public function normalizeCode(string $value): string
    {
        $value = strtoupper(trim($value));
        if ($value === '' || preg_match('/^[A-Z0-9][A-Z0-9_-]{1,49}$/', $value) !== 1) {
            throw new InvalidArgumentException('Tenant code must contain 2-50 uppercase letters, numbers, dashes, or underscores.');
        }
        return $value;
    }

    public function normalizeName(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Tenant name is required.');
        }
        return $value;
    }

    public function normalizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '' || preg_match('/^[a-z0-9](?:[a-z0-9-]{0,98}[a-z0-9])?$/', $value) !== 1) {
            throw new InvalidArgumentException('Tenant slug must be a lowercase URL-safe value.');
        }
        return $value;
    }

    public function normalizeDomain(string $value): string
    {
        $value = rtrim(trim($value), '.');
        if (
            $value === ''
            || str_contains($value, '://')
            || str_contains($value, '/')
            || str_contains($value, ':')
            || str_starts_with($value, '*.')
        ) {
            throw new InvalidArgumentException('A hostname without a protocol, wildcard, port, or path is required.');
        }

        if (preg_match('/[^\x20-\x7e]/', $value) === 1) {
            if (! function_exists('idn_to_ascii')) {
                throw new InvalidArgumentException('Internationalized domains require the PHP intl extension.');
            }

            $flags = defined('IDNA_NONTRANSITIONAL_TO_ASCII')
                ? IDNA_NONTRANSITIONAL_TO_ASCII
                : 0;
            $variant = defined('INTL_IDNA_VARIANT_UTS46')
                ? INTL_IDNA_VARIANT_UTS46
                : 0;
            $ascii = idn_to_ascii($value, $flags, $variant);
            if (! is_string($ascii) || $ascii === '') {
                throw new InvalidArgumentException('The internationalized domain is invalid.');
            }
            $value = $ascii;
        }

        $value = strtolower($value);
        $labels = explode('.', $value);
        $reservedTlds = ['example', 'invalid', 'localhost', 'local', 'test', 'internal'];
        $lastLabel = end($labels);
        $centralHosts = array_map(
            static fn (string $host): string => strtolower(rtrim(trim($host), '.')),
            $this->centralHosts,
        );

        if (
            strlen($value) > 253
            || count($labels) < 2
            || in_array($lastLabel, $reservedTlds, true)
            || in_array($value, ['localhost', 'localhost.localdomain'], true)
            || in_array($value, $centralHosts, true)
            || filter_var($value, FILTER_VALIDATE_IP) !== false
            || filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
        ) {
            throw new InvalidArgumentException('A valid public custom hostname is required.');
        }

        foreach ($labels as $label) {
            if ($label === '' || strlen($label) > 63) {
                throw new InvalidArgumentException('The custom hostname contains an invalid label.');
            }
        }

        return $value;
    }

    public function normalizeBillingInterval(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = $value === '' ? 'month' : $value;
        if (! in_array($value, ['month', 'quarter', 'year'], true)) {
            throw new InvalidArgumentException('Billing interval must be month, quarter, or year.');
        }
        return $value;
    }

    public function normalizeOptionalText(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);
        return $value === '' ? null : $value;
    }

    public function normalizeMetadata(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
