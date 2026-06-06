<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Contracts\SlugGeneratorInterface;

final class SlugGenerator implements SlugGeneratorInterface
{
    public function __construct(private readonly string $fallback)
    {
        if (trim($this->fallback) === '') {
            throw new InvalidArgumentException('Slug fallback cannot be empty.');
        }
    }

    public function generate(?string $preferredValue, ?string $sourceValue, ?string $fallback = null): string
    {
        $candidate = $this->normalizeInput($preferredValue);

        if ($candidate === null) {
            $candidate = $this->normalizeInput($sourceValue);
        }

        $slug = Str::slug((string) $candidate);

        if ($slug !== '') {
            return $slug;
        }

        $fallbackValue = $this->normalizeInput($fallback) ?? $this->fallback;
        $fallbackSlug = Str::slug(trim($fallbackValue));

        if ($fallbackSlug === '') {
            throw new InvalidArgumentException('Unable to generate slug from provided values.');
        }

        return $fallbackSlug;
    }

    private function normalizeInput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmedValue = trim($value);

        return $trimmedValue === '' ? null : $trimmedValue;
    }
}
