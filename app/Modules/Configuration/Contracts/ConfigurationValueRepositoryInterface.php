<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Configuration\Data\ConfigurationScopeContext;
use Modules\Configuration\Data\StoredConfigurationValue;

interface ConfigurationValueRepositoryInterface
{
    public function findExact(ConfigurationScopeContext $context, string $key): ?StoredConfigurationValue;
    /** @return LengthAwarePaginator<int, StoredConfigurationValue> */
    public function paginate(ConfigurationScopeContext $context, ?string $prefix, int $page, int $perPage): LengthAwarePaginator;
    /** @param array<string, mixed> $attributes */
    public function create(ConfigurationScopeContext $context, array $attributes): StoredConfigurationValue;
    /** @param array<string, mixed> $attributes */
    public function updateExpected(StoredConfigurationValue $current, int $expectedVersion, array $attributes): StoredConfigurationValue;
    public function deleteExpected(StoredConfigurationValue $current, int $expectedVersion): void;
}
