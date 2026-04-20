<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services\Concerns;

trait BuildsLocationPath
{
    private function normalizePathSegment(?string $code, string $name): string
    {
        $segment = $code !== null && trim($code) !== '' ? trim($code) : trim($name);
        $segment = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $segment));
        $segment = trim($segment, '-');

        return $segment !== '' ? $segment : 'location';
    }

    private function buildLocationPath(?string $parentPath, ?string $code, string $name): string
    {
        $segment = $this->normalizePathSegment($code, $name);

        return $parentPath !== null && $parentPath !== '' ? $parentPath.'/'.$segment : $segment;
    }
}
