<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts;

interface ConfigurationCacheInterface
{
    public function has(string $key): bool;

    public function get(string $key): mixed;

    public function put(string $key, mixed $value, ?int $ttlSeconds = null): void;

    public function forget(string $key): void;

    public function flush(): void;
}
