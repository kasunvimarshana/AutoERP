<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Services;

use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\SlugGeneratorInterface;

class SlugGenerator implements SlugGeneratorInterface
{
    public function __construct(private readonly string $fallback)
    {
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

        $fallbackValue = $fallback ?? $this->fallback;
        $fallbackSlug = Str::slug(trim($fallbackValue));

        return $fallbackSlug !== '' ? $fallbackSlug : $this->fallback;
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
