<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Contracts;

interface ConfigurationDomainServiceInterface
{
    public function normalizeKey(string $key): string;

    public function normalizeSource(?string $source): string;

    public function normalizeDescription(?string $description): ?string;

    public function parseCliValue(string $raw): mixed;

    /**
     * @return array{0: string, 1: string}
     */
    public function serializeValue(mixed $value): array;

    public function deserializeValue(string $storedValue, string $valueType): mixed;
}
