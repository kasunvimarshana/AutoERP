<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Services;

use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\SlugGeneratorInterface;

class SlugGenerator implements SlugGeneratorInterface
{
    public function generate(?string $preferredValue, ?string $sourceValue, string $fallback = 'n-a'): string
    {
        $candidate = $this->normalizeInput($preferredValue);

        if ($candidate === null) {
            $candidate = $this->normalizeInput($sourceValue);
        }

        $slug = Str::slug((string) $candidate);

        if ($slug !== '') {
            return $slug;
        }

        $fallbackSlug = Str::slug(trim($fallback));

        return $fallbackSlug !== '' ? $fallbackSlug : 'n-a';
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
